<?php

namespace App\Actions\Workflow;

use App\Mail\WorkflowNotificationMail;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SendNotificationAction
{
    /**
     * Execute a notification step and return output for the execution log.
     *
     * Step config shape:
     * {
     *   "channel": "email" | "slack" | "log",
     *   "to": "someone@example.com",          // email only
     *   "subject": "Order received",           // email only
     *   "message": "Hello {{customer_name}}", // all channels
     *   "slack_webhook_url": "https://...",   // slack only
     * }
     */
    public function execute(WorkflowStep $step, array $context): array
    {
        $config  = $step->config ?? [];
        $channel = $config['channel'] ?? 'log';
        $message = $this->interpolate($config['message'] ?? 'Workflow notification', $context);

        return match ($channel) {
            'email' => $this->sendEmail($config, $message),
            'slack' => $this->sendSlack($config, $message),
            default => $this->sendLog($step, $message),
        };
    }

    private function sendEmail(array $config, string $message): array
    {
        $to      = $config['to'] ?? throw new \InvalidArgumentException('Email notification requires a "to" address.');
        $subject = $config['subject'] ?? 'FlowForge Notification';

        Mail::to($to)->send(new WorkflowNotificationMail($subject, $message));

        return ['channel' => 'email', 'to' => $to, 'subject' => $subject, 'status' => 'sent'];
    }

    private function sendSlack(array $config, string $message): array
    {
        $webhookUrl = $config['slack_webhook_url']
            ?? throw new \InvalidArgumentException('Slack notification requires a "slack_webhook_url".');

        $response = Http::post($webhookUrl, ['text' => $message]);
        $response->throw();

        return ['channel' => 'slack', 'status' => 'sent'];
    }

    private function sendLog(WorkflowStep $step, string $message): array
    {
        // Fallback / dev channel — writes to Laravel log, no external call
        logger()->info("[FlowForge] Notification step [{$step->name}]: {$message}");

        return ['channel' => 'log', 'message' => $message, 'status' => 'logged'];
    }

    private function interpolate(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace("{{$key}}", $value, $template);
                $template = str_replace("{{ $key }}", $value, $template);
            }
        }

        return $template;
    }
}
