<?php

namespace App\Repos\Interfaces;

use DateTimeInterface;

use App\Models\{
    Verification,
};

interface VerificationRepo
{
    public function find($id);
    public function findOrFail($id);
    public function search(string $type, string $data);
    public function searchAvailable(string $type, string $data);
    public function getOrCreate(array $values, $verificable);
    public function create(array $values, $verificable);
    public function verify(Verification $v, string $code, string $data, string $type, DateTimeInterface $now);
    public function unverify(Verification $v);
    public function notify(Verification $v, $notifiable, $notification);
    public function getAvailable($verificable);
}
