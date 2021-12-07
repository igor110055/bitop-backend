<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Requests\Admin\{
    AgencyCreateRequest,
    AgencyUpdateRequest,
};
use App\Models\{
    Agency,
    Asset,
};
use App\Repos\Interfaces\{
    AgencyRepo,
    AssetRepo,
    UserRepo,
};


class AgencyController extends AdminController
{
    public function __construct(
        AgencyRepo $AgencyRepo,
        AssetRepo $AssetRepo,
        UserRepo $UserRepo
    ) {
        parent::__construct();
        $this->AgencyRepo = $AgencyRepo;
        $this->AssetRepo = $AssetRepo;
        $this->UserRepo = $UserRepo;
        $this->middleware(['role:super-admin']);
    }

    public function index()
    {
        return view('admin.agencies', [
            'agencies' => $this->AgencyRepo->getAll()
        ]);
    }

    public function show(Agency $agency)
    {
        $assets = $this->AssetRepo
            ->allByAgencyOrCreate($agency);
        return view('admin.agency', [
            'agency' => $agency,
            'assets' => $assets,
            'page_title' => $agency->id,
        ]);
    }

    public function create()
    {
        $agency = new Agency;

        return view('admin.agency_edit', [
            'action' => 'create',
            'route' => route('admin.agencies.store'),
            'agency' => $agency,
            'page_title' => '新增組織',
        ]);
    }

    public function edit(Agency $agency)
    {
        return view('admin.agency_edit', [
            'action' => 'edit',
            'route' => route('admin.agencies.update', ['agency' => $agency->id]),
            'agency' => $agency,
            'page_title' => $agency->name,
        ]);
    }

    public function store(AgencyCreateRequest $request)
    {
        $values = $request->validated();
        $values['id'] = strtolower($values['id']);

        try {
            $agency = $this->AgencyRepo
                ->create($values);
        } catch (Exception $e) {
            throw $e;
            return response('Agency id '.$values['id'].' has been used.', 409);
        }

        return redirect()->route('admin.agencies.show', ['agency' => $agency->id])->with('flash_message', ['message' => '組織已新增']);
    }

    public function update(Agency $agency, AgencyUpdateRequest $request)
    {
        $values = $request->validated();

        $this->AgencyRepo
            ->update($agency, $values);

        return redirect()->route('admin.agencies.show', ['agency' => $agency->id])->with('flash_message', ['message' => '組織資料編輯完成']);
    }

    public function getAgents(Agency $agency)
    {
        $agents = $agency->agents;
        return view('admin.agents', [
            'agency' => $agency,
            'agents' => $agents,
        ]);
    }

    public function createAgent(Agency $agency)
    {
        return view('admin.agent_create', [
            'agency' => $agency,
        ]);
    }

    public function storeAgent(Request $request, Agency $agency)
    {
        $user_id = $request->input('user_id');
        $user = $this->UserRepo
            ->findOrFail($user_id);
        $this->UserRepo
            ->update($user, [
                'agency_id' => $agency->id,
            ]);
        return redirect()->route('admin.agencies.agents', ['agency' => $agency->id])->with('flash_message', ['message' => '已新增業務']);
    }

    public function deleteAgent(Request $request, Agency $agency)
    {
        $user_id = $request->input('user_id');
        $user = $this->UserRepo
            ->findOrFail($user_id);
        $this->UserRepo
            ->update($user, [
                'agency_id' => null,
            ]);
        $request->session()->flash('flash_message', ['message' => '已刪除業務']);
        return response()->json();
    }
}
