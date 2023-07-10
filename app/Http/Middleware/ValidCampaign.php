<?php

namespace App\Http\Middleware;

use App\Models\Campaign;
use App\Repositories\CampaignRepository;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ValidCampaign
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('campaign')) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_invalid_campaign')],
                'error_code' => config('constants.error_code.campaign_invalid_middlewarecampaign')
            ], Response::HTTP_NOT_FOUND);
        }
        $campaignRepository = new CampaignRepository();
        $campaign = $campaignRepository->findByField('code', $request->header('campaign'))->first();
        if (empty($campaign)) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_not_found', ['attribute' => trans('message.campaign')])],
                'error_code' => config('constants.error_code.campaign_not_found_middlewarecampaign')
            ], Response::HTTP_NOT_FOUND);
        }
        if ($campaign->status != Campaign::STATUS_ACTIVE || $campaign->start_date > Carbon::now() || $campaign->end_date < Carbon::now()) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_invalid_campaign')],
                'error_code' => config('constants.error_code.campaign_invalid_middlewarecampaign')
            ], Response::HTTP_BAD_REQUEST);
        }
        return $next($request);
    }
}
