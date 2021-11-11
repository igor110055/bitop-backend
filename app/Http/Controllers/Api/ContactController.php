<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Traits\SecurityCodeTrait;
use App\Http\Requests\CreateContactRequest;
use App\Exceptions\{
    Core\BadRequestError,
    DuplicateRecordError
};
use App\Repos\Interfaces\{
    UserRepo,
    ContactRepo,
};
use App\Http\Resources\{
    ContactResource,
};

class ContactController extends AuthenticatedController
{
    use SecurityCodeTrait;

    public function __construct(ContactRepo $ContactRepo, UserRepo $UserRepo)
    {
        parent::__construct();
        $this->ContactRepo = $ContactRepo;
        $this->UserRepo = $UserRepo;
    }

    public function index()
    {
        $contacts = $this->ContactRepo->getContactList(auth()->user());
        return ContactResource::collection($contacts);
    }

    public function create(CreateContactRequest $request)
    {
        $values = $request->validated();
        $user = auth()->user();
        $contact_user = $this->UserRepo->findOrFail($values['contact_id']);

        # check security_code
        $this->checkSecurityCode($user, $values['security_code']);

        if ($this->ContactRepo->getContact($user, $contact_user->id)) {
            throw new DuplicateRecordError;
        }
        return new ContactResource($this->ContactRepo->createByUser($user, $values));
    }

    public function delete(Request $request)
    {
        $user_id = $request->input('contact_id');
        if (is_array($user_id)) {
            $this->bulkDelete($user_id);
        } elseif (is_string($user_id)) {
            $this->deleteContact($user_id);
        } else {
            throw new BadRequestError;
        }
        return response(null, 204);
    }

    protected function bulkDelete(Array $ids)
    {
        foreach ($ids as $user_id) {
            try {
                $this->deleteContact($user_id);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }

    protected function deleteContact($user_id)
    {
        if ($contact = $this->ContactRepo->getContact(auth()->user(), $user_id)) {
            $this->ContactRepo->delete($contact);
            return;
        }
        throw new BadRequestError('Contact user not found');
    }

}
