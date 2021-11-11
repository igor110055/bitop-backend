<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use DB;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;

use App\Exceptions\{
    Core\BadRequestError,
    Verification\ExpiredVerificationError,
    Verification\TooEarlyError,
    Verification\WrongCodeError,
};
use App\Models\{
    Verification,
    User,
};

class VerificationRepo implements \App\Repos\Interfaces\VerificationRepo
{
    public function __construct(Verification $verification) {
        $this->verification = $verification;
    }

    public function find($id)
    {
        return $this->verification->find($id);
    }

    public function findOrFail($id)
    {
        return $this->verification->findOrFail($id);
    }

    public function search(string $type, string $data, $verificable = null)
    {
        if ($verificable) {
            return $verificable
                ->verifications()
                ->where('type', $type)
                ->where('data', $data)
                ->orderBy('created_at', 'desc')
                ->first();
        } else {
            return $this->verification
                ->where('type', $type)
                ->where('data', $data)
                ->orderBy('created_at', 'desc')
                ->first();
        }
    }

    public function searchAvailable(string $type, string $data, $verificable = null)
    {
        if ($verification = $this->search($type, $data, $verificable)) {
            return $verification->is_available ? $verification : null;
        }
        return null;
    }

    public function getOrCreate(array $values, $verificable = null)
    {
        assert(isset($values['type'])); # verification must contains a type
        assert(isset($values['data'])); # verification must contains a data
        if ($existing = $this->searchAvailable(
            data_get($values, 'type'),
            data_get($values, 'data'),
            $verificable
        )) {
            return $existing;
        }
        return $this->create($values, $verificable);
    }

    public function create(array $values, $verificable = null)
    {
        assert(isset($values['type'])); # verification must contains a type
        assert(isset($values['data'])); # verification must contains a data
        $type = $values['type'];
        assert(in_array($type, Verification::TYPES)); # make sure that type is valid
        $values['code'] = generate_code(Verification::codeLength($type), Verification::codeType($type));
        $values['expired_at'] = Carbon::now()->addMinutes(
            Verification::timeout($type)
        );
        $values['channel'] = data_get($values, 'channel', Verification::channel($type)); # set default channel if not presented in $values

        if ($verificable && ($verificable instanceof \Illuminate\Database\Eloquent\Model)) {
            return $verificable->verifications()->create($values);
        }
        return $this->verification->create($values);
    }

    public function verify(Verification $v, string $code, string $data = null, string $type = null, DateTimeInterface $now = null)
    {
        $now = $now ?: now();
        $v = Verification::lockForUpdate()->find($v->id);
        if (!is_null($type) and ($v->type !== $type)) {
            throw new BadRequestError('wrong verification type');
        }
        if (!is_null($data) and ($v->data !== $data)) {
            throw new BadRequestError('wrong verification data', [
                'data' => "Received data is {$data} while data to verify is {$v->data}"
            ]);
        }
        if ($v->is_expired or $v->is_verified) {
            throw new ExpiredVerificationError;
        }
        if ($v->tries >= config('core.verification.max_tries')) {
            throw new ExpiredVerificationError;
        }
        if ($v->code !== $code) {
            $v->update([
                'tries' => DB::raw('tries + 1')
            ]);
            throw new WrongCodeError;
        }

        $v->verified_at = $now;
        $v->save();
    }

    public function unverify(Verification $v)
    {
        $v = Verification::lockForUpdate()->find($v->id);
        $v->verified_at = null;
        $v->save();
    }

    public function notify(Verification $v, $notifiable, $notification)
    {
        if ($v->is_expired
            or $v->is_verified
            or ($v->tries >= config('core.verification.max_tries'))
        ) {
            throw new ExpiredVerificationError;
        }

        $now = now();

        if ($notified_at = data_get($v,'notified_at')) {
            $resend_after = $notified_at->addSeconds(config('core.verification.resend_after'));
            if ($resend_after->greaterThan($now)) {
                throw new TooEarlyError;
            }
        }

        $v->notified_at = $now;
        $v->save();

        if (in_array('nexmo', $v->channel) && !config('services.nexmo.key')) {
            return;
        }

        $notifiable->notify($notification);
    }

    public function getAvailable($verificable)
    {
        return $verificable->verifications()
            ->whereNull('verified_at')
            ->where('expired_at', '>', Carbon::now())
            ->latest()
            ->first();
    }
}
