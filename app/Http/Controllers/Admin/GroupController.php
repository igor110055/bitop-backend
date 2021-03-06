<?php

namespace App\Http\Controllers\Admin;

use DB;
use Dec\Dec;
use Illuminate\Http\Request;

use App\Http\Requests\Admin\{
    GroupCreateRequest,
    GroupUpdateRequest,
};
use App\Models\{
    Group,
    FeeSetting,
    Limitation,
    Config,
    GroupApplication,
    AdminAction,
    User,
};
use App\Repos\Interfaces\{
    FeeSettingRepo,
    FeeCostRepo,
    GroupRepo,
    ShareSettingRepo,
    LimitationRepo,
    ConfigRepo,
    GroupApplicationRepo,
    AdminActionRepo,
    UserRepo,
};
use App\Services\{
    FeeServiceInterface,
    ExchangeServiceInterface,
};


class GroupController extends AdminController
{
    public function __construct(
        FeeSettingRepo $FeeSettingRepo,
        FeeCostRepo $FeeCostRepo,
        ConfigRepo $ConfigRepo,
        GroupRepo $GroupRepo,
        ShareSettingRepo $ShareSettingRepo,
        LimitationRepo $LimitationRepo,
        GroupApplicationRepo $GroupApplicationRepo,
        AdminActionRepo $AdminActionRepo,
        UserRepo $UserRepo
    ) {
        parent::__construct();
        $this->FeeSettingRepo = $FeeSettingRepo;
        $this->FeeCostRepo = $FeeCostRepo;
        $this->ConfigRepo = $ConfigRepo;
        $this->GroupRepo = $GroupRepo;
        $this->ShareSettingRepo = $ShareSettingRepo;
        $this->LimitationRepo = $LimitationRepo;
        $this->GroupApplicationRepo = $GroupApplicationRepo;
        $this->AdminActionRepo = $AdminActionRepo;
        $this->UserRepo = $UserRepo;
        $this->coins = array_keys(config('coin'));

        $this->middleware(
            ['can:edit-groups'],
            ['only' => [
                'update',
                'create',
                'store',
                'createShareSetting',
                'storeShareSetting',
                'destoryShareSetting',
                'editFeeSettings',
                'editLimitations',
                'storeLimitation',
                'verifyApplication',
            ]]
        );

        $this->middleware(
            ['can:edit-limitations'],
            ['only' => [
                'editLimitations',
                'storeLimitation',
            ]]
        );
    }

    public function index()
    {
        return view('admin.groups', [
            'groups' => $this->GroupRepo->getAllGroups()
        ]);
    }

    public function show(Group $group)
    {
        return view('admin.group', [
            'group' => $group,
            'page_title' => $group->id,
        ]);
    }

    public function update(Group $group, GroupUpdateRequest $request)
    {
        $values = $request->validated();
        $user = $this->UserRepo->findOrFail($values['user_id']);
        $own_groups = $user->groups;

        if ($own_groups->isNotEmpty()) {
            foreach ($own_groups as $own_group) {
                if ($group->id !== $own_group->id) {
                    return redirect()->route('admin.groups.show', ['group' => $group->id])->with('flash_message', ['message' => "?????? {$user->username} ???????????? {$own_group->id} ????????????????????????????????????????????????"]);
                }
            }
        }

        $this->GroupRepo->update($group, $values);

        $this->UserRepo->update($user, [
            'group_id' => $group->id,
        ]);

        $this->AdminActionRepo->createByApplicable($group, [
            'admin_id' => \Auth::id(),
            'type' => AdminAction::TYPE_GROUP_UPDATE,
            'description' => json_encode($values),
        ]);

        return redirect()->route('admin.groups.show', ['group' => $group->id])->with('flash_message', ['message' => '????????????????????????']);
    }

    public function create()
    {
        $group = new Group;

        return view('admin.group_create', [
            'group' => $group,
            'page_title' => '????????????',
        ]);
    }

    public function store(GroupCreateRequest $request)
    {
        $values = $request->validated();
        $values['id'] = strtolower($values['id']);

        $user = $this->UserRepo->findOrFail($values['user_id']);
        $own_groups = $user->groups;
        if ($own_groups->isNotEmpty()) {
            $own_group = $own_groups->first();
            return redirect()->route('admin.groups.create')->with('flash_message', ['message' => "?????? {$user->username} ???????????? {$own_group->id} ????????????????????????????????????????????????", 'class' => 'danger']);
        }

        try {
            $group = $this->GroupRepo
                ->create($values);
        } catch (Exception $e) {
            return response('Group id '.$values['id'].' has been used.', 409);
        }
        $this->UserRepo->update($user, [
            'group_id' => $group->id,
        ]);

        return redirect()->route('admin.groups.show', ['group' => $group->id])->with('flash_message', ['message' => '???????????????']);
    }

    public function getUsers(Group $group)
    {
        return view('admin.users', [
            'group' => $group,
        ]);
    }

    public function getShareSettings(Group $group)
    {
        $share_compositions = $this->ShareSettingRepo
            ->getComposition($group, false, true);

        return view('admin.share_settings', [
            'group' => $group,
            'share_settings' => $share_compositions['share_settings'],
        ]);
    }

    public function createShareSetting(Group $group)
    {
        $share_compositions = $this->ShareSettingRepo
            ->getComposition($group, false, false);

        if (Dec::gte($share_compositions['total_percentage'], 100)) {
            return redirect()
                ->route('admin.groups.share-settings', ['group' => $group->id])
                ->with('flash_message', ['message' => "????????????????????????100%?????????????????????????????????"]);
        }

        return view('admin.share_setting_add', [
            'group' => $group,
        ]);
    }

    public function storeShareSetting(Group $group, Request $request)
    {
        $values = $request->validate([
            'user_id' => 'required|string|exists:users,id',
            'percentage' => "required|numeric|min:0|max:100",
        ]);

        $share_compositions = $this->ShareSettingRepo
            ->getComposition($group, false, false);

        if (Dec::gt((Dec::add($values['percentage'], $share_compositions['total_percentage'])), 100)) {
            return redirect()
                ->route('admin.groups.share-settings', ['group' => $group->id])
                ->with('flash_message', ['message' => "???????????????????????????100%??????????????????"]);
        }

        $share_compositions = $this->ShareSettingRepo
            ->create([
                'group_id' => $group->id,
                'user_id' => $values['user_id'],
                'percentage' => $values['percentage'],
        ]);

        return redirect()
            ->route('admin.groups.share-settings', ['group' => $group->id])
            ->with('flash_message', ['message' => '???????????????????????????']);
    }

    public function destoryShareSetting(Group $group, Request $request)
    {
        $values = $request->validate([
            'share_setting_id' => 'required|string|exists:share_settings,id',
        ]);

        $this->ShareSettingRepo
            ->deactivate($values['share_setting_id']);

        return response(null, 204);
    }

    public function getFeeSettings(Group $group)
    {
        $range_types = FeeSetting::RANGE_TYPES;
        $fix_types = FeeSetting::FIX_TYPES;

        foreach ($range_types as $type) {
            foreach ($this->coins as $coin) {
                $range_settings[$type][$coin] = $this->FeeSettingRepo
                    ->get($coin, $type, $group);
            }
        }

        foreach ($fix_types as $type) {
            foreach ($this->coins as $coin) {
                $fix_settings[$type][$coin] = $this->FeeSettingRepo
                    ->get($coin, $type, $group);
            }
        }

        foreach ($this->coins as $coin) {
            $fee_cost = data_get($this->FeeCostRepo->getLatest($coin), 'cost');
            $fee_costs[$coin] = is_null($fee_cost) ? $fee_cost : trim_zeros($fee_cost);
            $base = $this->ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.base");
            $fee_base[$coin] = is_null($base) ? $base : trim_zeros($base);
        }

        return view('admin.fee_settings', [
            'group' => $group,
            'range_settings' => $range_settings,
            'fix_settings' => $fix_settings,
            'withdrawal_fee_costs' => $fee_costs,
            'withdrawal_fee_base' => $fee_base,
            'withdrawal_fee' => $this->getWithdrawalFee($group),
        ]);
    }

    protected function getWithdrawalFee(Group $group)
    {
        $FeeService = app()->make(FeeServiceInterface::class);
        $ExchangeService = app()->make(ExchangeServiceInterface::class);
        foreach ($this->coins as $coin) {
            $amount = $FeeService->getWithdrawalFee($coin, $group);
            $fee[$coin]['amount'] = trim_zeros($amount);
            $fee[$coin]['price'] = $ExchangeService->coinToBaseValue($coin, $amount);
        }
        return $fee;
    }

    public function editFeeSettings(Group $group, $type, $coin, ConfigRepo $ConfigRepo)
    {
        if (in_array($type, FeeSetting::RANGE_TYPES)) {
            return view('admin.fee_settings_edit', [
                'type' => $type,
                'coin' => $coin,
                'group' => $group,
                'data' => [
                    'type' => $type,
                    'coin' => $coin,
                    'applicable_id' => $group->id,
                ],
            ]);
        } elseif (in_array($type, FeeSetting::FIX_TYPES)) {
            return view('admin.withdrawal_fee_edit', [
                'group' => $group,
                'type' => $type,
                'coin' => $coin,
                'base' => $ConfigRepo->get(Config::ATTRIBUTE_WITHDRAWAL_FEE_FACTOR, "$coin.base"),
                'discount' => data_get($this->FeeSettingRepo->getFixed($coin, $type, $group), 'value'),
            ]);
        }
    }

    public function getLimitations(Group $group)
    {
        $types = Limitation::TYPES;
        foreach ($types as $type) {
            foreach ($this->coins as $coin) {
                $limitations[$type][$coin] = $this->LimitationRepo
                    ->getLatestLimitation($type, $coin, $group);
            }
        }
        return view('admin.limitations', [
            'limitations' => $limitations,
            'group' => $group,
        ]);
    }

    public function editLimitations(Group $group, $type, $coin)
    {
        $limitation = $this->LimitationRepo->getLatestLimitation($type, $coin, $group);
        return view('admin.limitation_edit', [
            'type' => $type,
            'coin' => $coin,
            'limitation' => $limitation,
            'group' => $group,
            'active' => $this->LimitationRepo->getLatestLimitationByClass($type, $coin, $group) ? true : false,
        ]);
    }

    public function storeLimitation(Group $group, Request $request)
    {
        $values = $request->all();

        # reset
        if ($request->input('reset')) {
            DB::transaction(function () use ($values, $group) {
                $limitation = $this->LimitationRepo->getLatestLimitationByClass($values['type'], $values['coin'], $group);
                $limitation->update(['is_active' => false]);
            });
            return redirect()->route('admin.groups.limitations', ['group' => $group])->with('flash_message', ['message' => '??????????????????']);
        }

        # store
        DB::transaction(function () use ($values, $group) {
            if ($limitation = $this->LimitationRepo->getLatestLimitationByClass($values['type'], $values['coin'], $group)) {
                if ($limitation->is_active) {
                    $limitation->update(['is_active' => false]);
                }
            }
            $group->limitations()->create([
                'coin' => $values['coin'],
                'type' => $values['type'],
                'min' => $values['min'],
                'max' => $values['max'],
            ]);
        });
        return redirect()->route('admin.groups.limitations', ['group' => $group])->with('flash_message', ['message' => '????????????']);
    }

    public function getApplications()
    {
        $applications = $this->GroupApplicationRepo->getAll();

        return view('admin.group_applications', [
            'applications' => $applications,
        ]);
    }

    public function getApplication(GroupApplication $application, Request $request)
    {
        return view('admin.group_application', [
            'application' => $application,
        ]);
    }

    public function verifyApplication(GroupApplication $application, Request $request)
    {
        $values = $request->all();
        DB::transaction(function () use ($application, $values) {
            if ($values['action'] === GroupApplication::STATUS_PASS) {
                $this->GroupApplicationRepo->update($application, [
                    'status' => GroupApplication::STATUS_PASS,
                ]);
                $this->AdminActionRepo->createByApplicable($application, [
                    'admin_id' => \Auth::id(),
                    'type' => AdminAction::TYPE_GROUP_APPLICATION_PASS,
                    'description' => $values['description'],
                ]);
            } elseif ($values['action'] === GroupApplication::STATUS_REJECT) {
                $this->GroupApplicationRepo->update($application, [
                    'status' => GroupApplication::STATUS_REJECT,
                ]);
                $this->AdminActionRepo->createByApplicable($application, [
                    'admin_id' => \Auth::id(),
                    'type' => AdminAction::TYPE_GROUP_APPLICATION_REJECT,
                    'description' => $values['description'],
                ]);
            }
        });
        return redirect()->route('admin.groups.applications')->with('flash_message', ['message' => "??????{$application->group_name}????????????"]);
    }

    public function delete(Group $group)
    {
        $groups = $this->GroupRepo->getJoinableGroupIds()->toArray();
        $groups = array_diff($groups, [$group->id]);
        return view('admin.group_delete', [
            'group' => $group,
            'groups' => array_combine($groups, $groups),
        ]);
    }

    public function destroy(Group $group, Request $request)
    {
        $new_group = $this->GroupRepo->findOrFail($request->input('group_id'));
        if ($group->id === Group::DEFAULT_GROUP_ID) {
            return redirect()->route('admin.groups.delete', ['group' => $group])->with('flash_message', ['message' => "?????????Default ??????????????????", 'class' => 'danger']);
        }
        if ($group->id === $new_group->id) {
            return redirect()->route('admin.groups.delete', ['group' => $group])->with('flash_message', ['message' => "?????????????????????????????????", 'class' => 'danger']);
        }

        User::where('group_id', $group->id)
            ->update([
                'group_id' => $new_group->id,
            ]);
        $group->delete();
        return redirect()->route('admin.groups.index')->with('flash_message', ['message' => "?????? {$group->id} ?????????"]);
    }
}
