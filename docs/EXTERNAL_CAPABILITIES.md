# External Capabilities

Other plugins may register a blockable action without creating a hard
dependency on Torah.

Register a callback during plugin initialization:

```php
$PLUGIN_HOOKS['torah_register_capabilities']['provider'] = static function (array $params): void {
    $registry = $params['registry'];
    $registry->register('ticket.custom_action', __('Custom action', 'provider'), 'provider');
};
```

The provider remains responsible for its native GLPI authorization and its
backend enforcement. After native authorization succeeds, it may ask Torah for
the complementary decision:

```php
$decision = \GlpiPlugin\Torah\Application\PolicyApi::decideForTicket(
    'ticket.custom_action',
    $ticketId,
);

if (!$decision->allowed) {
    // Reject the provider action without logging ticket content or field values.
}
```

If Torah is absent or inactive, the provider must continue normally and must
not attempt to load Torah classes. Providers should check plugin availability
before calling the API.
