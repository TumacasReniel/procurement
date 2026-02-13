<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\User;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Http\Resources\UserResource;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $activeSurvey = Survey::where('is_active', true)->latest()->first();

        $status = true;
        $surveyRequired = false;
        $surveyQuestions = [];

        if ($user && $activeSurvey) {
            $status = $user->profile->is_completed;
            $survey_id = $activeSurvey->id;
            $hasAnswered = SurveyAnswer::where('user_id', $user->id)
                ->where('survey_id', $activeSurvey->id)
                ->exists();

            if (!$hasAnswered) {
                $surveyRequired = true;
                $surveyQuestions = SurveyQuestion::where('is_active',1)->get()->map(function ($item) use ($survey_id){
                    return [
                        'id' => $item->id,
                        'question' => $item->question,
                        'rating' => null,
                        'color' => null,
                        'survey_id' => $survey_id
                    ];
                });
            }
        }

        return [
            ...parent::share($request),
            'user' => (\Auth::check()) ? new UserResource(User::with('profile','organization.position')->where('id',\Auth::user()->id)->first()) : null,
            'roles' => (\Auth::check()) ? \Auth::user()->roles()->where('user_roles.is_active', 1)->pluck('name') : null,
            'flash' => [
                'data'    => session('data') ?? null,
                'message' => session('message') ?? null,
                'info'    => session('info') ?? null,
                'status'  => session('status') ?? null,
                'type'    => session('type') ?? null,
            ],
            'updateRequired' => ($status == 0) ? true : false, 
            'surveyRequired' => $surveyRequired,
            'surveyQuestions' => $surveyQuestions
        ];
    }
}
