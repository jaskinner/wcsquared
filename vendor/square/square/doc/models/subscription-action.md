
# Subscription Action

Represents an action as a pending change to a subscription.

## Structure

`SubscriptionAction`

## Fields

| Name | Type | Tags | Description | Getter | Setter |
|  --- | --- | --- | --- | --- | --- |
| `id` | `?string` | Optional | The ID of an action scoped to a subscription. | getId(): ?string | setId(?string id): void |
| `type` | [`?string (SubscriptionActionType)`](../../doc/models/subscription-action-type.md) | Optional | Supported types of an action as a pending change to a subscription. | getType(): ?string | setType(?string type): void |
| `effectiveDate` | `?string` | Optional | The `YYYY-MM-DD`-formatted date when the action occurs on the subscription. | getEffectiveDate(): ?string | setEffectiveDate(?string effectiveDate): void |
| `phases` | [`?(Phase[])`](../../doc/models/phase.md) | Optional | A list of Phases, to pass phase-specific information used in the swap. | getPhases(): ?array | setPhases(?array phases): void |
| `newPlanVariationId` | `?string` | Optional | The target subscription plan variation that a subscription switches to, for a `SWAP_PLAN` action. | getNewPlanVariationId(): ?string | setNewPlanVariationId(?string newPlanVariationId): void |

## Example (as JSON)

```json
{
  "id": "id0",
  "type": "RESUME",
  "effective_date": "effective_date0",
  "phases": [
    {
      "uid": "uid5",
      "ordinal": 207,
      "order_template_id": "order_template_id7",
      "plan_phase_uid": "plan_phase_uid1"
    },
    {
      "uid": "uid6",
      "ordinal": 208,
      "order_template_id": "order_template_id8",
      "plan_phase_uid": "plan_phase_uid2"
    }
  ],
  "new_plan_variation_id": "new_plan_variation_id0"
}
```

