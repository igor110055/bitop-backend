<?php

namespace App\Repos\DB;

use App\Models\{
    User,
    Contact,
};

use Carbon\Carbon;

class ContactRepo implements \App\Repos\Interfaces\ContactRepo
{
    protected $contact;

    public function __construct(Contact $contact) {
        $this->contact = $contact;
    }

    public function find($id)
    {
        return $this->contact->find($id);
    }

    public function findOrFail($id)
    {
        return $this->contact->findOrFail($id);
    }

    public function getContactList(User $user)
    {
        return $user->contacts;
    }

    public function getContact(User $user, $contact_user_id)
    {
        return $user->contacts()
            ->where('contact_id', $contact_user_id)
            ->first();
    }

    public function createByUser(User $user, $values)
    {
        return $user->contacts()->create($values);
    }

    public function delete(Contact $contact)
    {
        return $contact->delete();
    }
}
