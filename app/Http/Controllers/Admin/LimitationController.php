<?php

namespace App\Http\Controllers\Admin;

use DB;
use App\Http\Requests\Admin\{
    LimitationCreateRequest,
};
use App\Repos\Interfaces\{
    LimitationRepo,
};
use App\Models\Limitation;

class LimitationController extends AdminController
{
    public function __construct(
        LimitationRepo $LimitationRepo
    ) {
        parent::__construct();
        $this->LimitationRepo = $LimitationRepo;
    }

    public function index()
    {
        $types = Limitation::TYPES;
        $coins = array_keys(config('coin'));
        foreach ($types as $type) {
            foreach ($coins as $coin) {
                $limitations[$type][$coin] = $this->LimitationRepo
                    ->getLatestLimitation($type, $coin);
            }
        }
        return view('admin.limitations', [
            'limitations' => $limitations,
        ]);
    }

    public function edit($type, $coin)
    {
        $limitation = $this->LimitationRepo->getLatestLimitation($type, $coin);
        return view('admin.limitation_edit', [
            'type' => $type,
            'coin' => $coin,
            'limitation' => $limitation,
        ]);
    }

    public function store(LimitationCreateRequest $request)
    {
        $values = $request->validated();
        DB::transaction(function () use ($values) {
            if ($limitation = $this->LimitationRepo->getLatestLimitation($values['type'], $values['coin'])) {
                if ($limitation->is_active) {
                    $limitation->update(['is_active' => false]);
                }
            }
            $this->LimitationRepo->create([
                'coin' => $values['coin'],
                'type' => $values['type'],
                'min' => $values['min'],
                'max' => $values['max'],
            ]);
        });
        return redirect()->route('admin.limitations.index')->with('flash_message', ['message' => '設定完成']);
    }
}
