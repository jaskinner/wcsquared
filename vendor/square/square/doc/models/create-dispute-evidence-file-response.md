
# Create Dispute Evidence File Response

Defines the fields in a `CreateDisputeEvidenceFile` response.

## Structure

`CreateDisputeEvidenceFileResponse`

## Fields

| Name | Type | Tags | Description | Getter | Setter |
|  --- | --- | --- | --- | --- | --- |
| `errors` | [`?(Error[])`](../../doc/models/error.md) | Optional | Any errors that occurred during the request. | getErrors(): ?array | setErrors(?array errors): void |
| `evidence` | [`?DisputeEvidence`](../../doc/models/dispute-evidence.md) | Optional | - | getEvidence(): ?DisputeEvidence | setEvidence(?DisputeEvidence evidence): void |

## Example (as JSON)

```json
{
  "evidence": {
    "dispute_id": "bVTprrwk0gygTLZ96VX1oB",
    "evidence_file": {
      "filename": "customer-interaction.jpg",
      "filetype": "image/jpeg"
    },
    "id": "TOomLInj6iWmP3N8qfCXrB",
    "uploaded_at": "2022-05-18T16:01:10.000Z",
    "evidence_id": "evidence_id0",
    "evidence_text": "evidence_text6"
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

