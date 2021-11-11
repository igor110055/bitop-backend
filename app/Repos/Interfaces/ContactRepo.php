<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    Contact,
};

interface ContactRepo
{
    public function find($id);
    public function findOrFail($id);
    public function createByUser(User $user, $values);
    public function getContactList(User $user);
    public function getContact(User $user, $contact_user_id);
    public function delete(Contact $contact);
}
