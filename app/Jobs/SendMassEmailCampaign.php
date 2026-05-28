<?php

namespace App\Jobs;

use App\Models\MassEmailCampaign;
use App\Models\MassEmailRecipient;
use App\Notifications\MassEmailNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMassEmailCampaign implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = MassEmailCampaign::find($this->campaignId);

        if (! $campaign) {
            Log::warning('SendMassEmailCampaign: campania no encontrada', ['id' => $this->campaignId]);

            return;
        }

        if ($campaign->status === 'enviado') {
            Log::info('SendMassEmailCampaign: campania ya enviada, abortando', ['id' => $campaign->id]);

            return;
        }

        $campaign->update(['status' => 'enviando']);

        $users = $campaign->resolveRecipients();

        if ($users->isEmpty()) {
            $campaign->update([
                'status' => 'enviado',
                'sent_at' => now(),
                'recipients_count' => 0,
                'sent_count' => 0,
                'failed_count' => 0,
            ]);

            return;
        }

        $campaign->update(['recipients_count' => $users->count()]);

        $sent = 0;
        $failed = 0;

        foreach ($users as $user) {
            $recipient = MassEmailRecipient::updateOrCreate(
                ['campaign_id' => $campaign->id, 'user_id' => $user->id],
                ['email' => $user->email, 'status' => 'pendiente']
            );

            try {
                $user->notify(new MassEmailNotification($campaign));
                $recipient->update([
                    'status' => 'enviado',
                    'sent_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $e) {
                $recipient->update([
                    'status' => 'fallido',
                    'error_message' => mb_substr($e->getMessage(), 0, 1000),
                ]);
                $failed++;
                Log::error('Mass email send failed', [
                    'campaign_id' => $campaign->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $campaign->update([
            'status' => 'enviado',
            'sent_at' => now(),
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);
    }
}
