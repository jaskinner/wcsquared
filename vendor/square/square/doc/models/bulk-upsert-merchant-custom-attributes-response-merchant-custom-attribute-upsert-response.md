
# Bulk Upsert Merchant Custom Attributes Response Merchant Custom Attribute Upsert Response

Represents a response for an individual upsert request in a [BulkUpsertMerchantCustomAttributes](../../doc/apis/merchant-custom-attributes.md#bulk-upsert-merchant-custom-attributes) operation.

## Structure

`BulkUpsertMerchantCustomAttributesResponseMerchantCustomAttributeUpsertResponse`

## Fields

| Name | Type | Tags | Description | Getter | Setter |
|  --- | --- | --- | --- | --- | --- |
| `merchantId` | `?string` | Optional | The ID of the merchant associated with the custom attribute. | getMerchantId(): ?string | setMerchantId(?string merchantId): void |
| `customAttribute` | [`?CustomAttribute`](../../doc/models/custom-attribute.md) | Optional | A custom attribute value. Each custom attribute value has a corresponding<br>`CustomAttributeDefinition` object. | getCustomAttribute(): ?CustomAttribute | setCustomAttribute(?CustomAttribute customAttribute): void |
| `errors` | [`?(Error[])`](../../doc/models/error.md) | Optional | Any errors that occurred while processing the individual request. | getErrors(): ?array | setErrors(?array errors): void |

## Example (as JSON)

```json
{
  "merchant_id": "merchant_id0",
  "custom_attribute": {
    "key": "key2",
    "value": {
      "key1": "val1",
      "key2": "val2"
    },
    "version": 102,
    "visibility": "VISIBILITY_READ_ONLY",
    "definition": {
      "key": "key2",
      "schema": {
        "key1": "val1",
        "key2": "val2"
      },
      "name": "name2",
      "description": "description2",
      "visibility": "VISIBILITY_READ_ONLY"
    }
  },
  "errors": [
    {
      "category": "AUTHENTICATION_ERROR",
      "code": "REFUND_ALREADY_PENDING",
      "detail": "detail1",
      "field": "field9"
    },
    {
      "category": "INVALID_REQUEST_ERROR",
      "code": "PAYMENT_NOT_REFUNDABLE",
      "detail": "detail2",
      "field": "field0"
    },
    {
      "category": "RATE_LIMIT_ERROR",
      "code": "REFUND_DECLINED",
      "detail": "detail3",
      "field": "field1"
    }
  ]
}
```

