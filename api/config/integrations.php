<?php

return [
    'discord' => [
        'webhooks' => [
            // Placeholder for future outbound webhook targets.
            'enabled' => (bool) env('DISCORD_WEBHOOKS_ENABLED', false),
        ],
    ],
];
