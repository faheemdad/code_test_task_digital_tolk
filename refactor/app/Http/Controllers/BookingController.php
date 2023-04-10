<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $response = null;
        $authenticatedUser = $request->__authenticatedUser;
        $isAdmin = $authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID');

        if ($request->has('user_id')) {
            $response = $this->repository->getUsersJobs($request->get('user_id'));
        } elseif ($isAdmin) {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {

        $job = $this->repository->getModel()->getRelation('translatorJobRel')
            ? $this->repository->with('translatorJobRel.user')->find($id)
            : null;
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $current_user = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $current_user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $response = null;
        if($request->has('user_id')) {
            $user_id = $request->get('user_id');
            $response = $this->repository->getUsersJobsHistory($user_id, $request);
        }
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $response = null;
        if($request->has('job_id') && !empty($request->__authenticatedUser)) {
            $data = $request->get('job_id');
            $user = $request->__authenticatedUser;
            $response = $this->repository->acceptJobWithId($data, $user);
        }
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->endJob($data);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->only(['distance', 'time', 'jobid', 'session_time', 'flagged', 'admincomment', 'manually_handled', 'by_admin']);

        if ($data['flagged'] === 'true' && empty($data['admincomment'])) {
            return response('Please, add comment');
        }

        $distance = $data['distance'] ?? '';
        $time = $data['time'] ?? '';
        $session = $data['session_time'] ?? '';
        $flagged = $data['flagged'] === 'true' ? 'yes' : 'no';
        $manuallyHandled = $data['manually_handled'] === 'true' ? 'yes' : 'no';
        $byAdmin = $data['by_admin'] === 'true' ? 'yes' : 'no';
        $adminComment = $data['admincomment'] ?? '';

        if ($time || $distance) {
            Distance::where('job_id', $data['jobid'])->update(['distance' => $distance, 'time' => $time]);
        }

        if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {
            Job::where('id', $data['jobid'])->update([
                'admin_comments' => $adminComment,
                'flagged' => $flagged,
                'session_time' => $session,
                'manually_handled' => $manuallyHandled,
                'by_admin' => $byAdmin,
            ]);
        }

        return response('Record updated!');

    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $response = ['success' => 'Push not sent'];
        if (!empty($data['jobid'])) {
            $job = $this->repository->find($data['jobid']);
            if (!empty($job)) {
                $job_data = $this->repository->jobToData($job);
                $this->repository->sendNotificationTranslator($job, $job_data, '*');
                $response = ['success' => 'Push sent'];
            }
        }
        return response($response);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);

        try {
            if (!empty($job)) {
                $this->repository->sendSMSNotificationToTranslator($job);
                return response(['success' => 'SMS sent']);
            } else {
                return response(['error' => 'SMS not sent, job is not found!']);
            }
        } catch (\Exception $e) {
            return response(['error' => $e->getMessage()], 500);
        }

    }

}
