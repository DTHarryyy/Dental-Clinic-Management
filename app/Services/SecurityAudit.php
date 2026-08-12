<?php

namespace App\Services;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SecurityAudit
{
    public function authorizationDenied(Request $request): void
    {
        if ($request->attributes->get('security_denial_audited')) {
            return;
        }

        $request->attributes->set('security_denial_audited', true);
        $target = collect($request->route()?->parameters() ?? [])
            ->first(fn ($value) => $value instanceof Model);
        $ability = collect($request->route()?->gatherMiddleware() ?? [])
            ->first(fn (string $middleware) => str_starts_with($middleware, 'can:'));

        $this->record(
            'authorization.denied',
            'denied',
            $request->user(),
            $ability ? str($ability)->after('can:')->before(',')->toString() : null,
            $target,
            request: $request,
        );
    }

    public function record(
        string $event,
        string $result,
        ?User $actor = null,
        ?string $ability = null,
        Model|string|null $target = null,
        array $context = [],
        ?Request $request = null,
    ): void {
        try {
            if (! Schema::hasTable('security_audit_logs')) {
                return;
            }

            [$targetType, $targetId] = $this->targetIdentity($target);
            $request ??= request();

            SecurityAuditLog::query()->create([
                'actor_user_id' => $actor?->getKey(),
                'actor_role' => $actor?->role,
                'event' => $event,
                'ability' => $ability,
                'result' => $result,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'route_name' => $request?->route()?->getName(),
                'method' => $request?->method(),
                'ip_address' => $request?->ip(),
                'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
                'context' => $this->safeContext($context),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Security audit event could not be persisted.', [
                'event' => $event,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /** @return array{0: ?string, 1: ?string} */
    private function targetIdentity(Model|string|null $target): array
    {
        if ($target instanceof Model) {
            return [$target->getMorphClass(), (string) $target->getKey()];
        }

        return [$target, null];
    }

    private function safeContext(array $context): ?array
    {
        $allowed = array_intersect_key($context, array_flip([
            'old_role', 'new_role', 'old_status', 'new_status', 'reason',
        ]));

        return $allowed === [] ? null : $allowed;
    }
}
