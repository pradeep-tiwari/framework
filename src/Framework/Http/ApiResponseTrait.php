<?php

namespace Lightpack\Http;

trait ApiResponseTrait
{
    /**
     * Send a successful API response with a standard envelope.
     *
     * @param mixed $data Response payload.
     * @param string|null $message Optional human-readable message.
     * @param int $status HTTP status code (default: 200).
     */
    public function respondSuccess($data, ?string $message = null, int $status = 200): Response
    {
        return $this->respond(true, $data, $message, null, $status);
    }

    /**
     * Send a 201 Created response.
     *
     * @param mixed $data Response payload.
     * @param string|null $message Optional human-readable message.
     */
    public function respondCreated($data, ?string $message = null): Response
    {
        return $this->respondSuccess($data, $message, 201);
    }

    /**
     * Send a 204 No Content response.
     */
    public function respondNoContent(): Response
    {
        return response()
            ->setStatus(204)
            ->setType('application/json')
            ->setBody('');
    }

    /**
     * Send a 404 Not Found response.
     *
     * @param string|null $message Optional error message.
     */
    public function respondNotFound(?string $message = null): Response
    {
        return $this->respondError($message ?? 'Resource not found.', 404);
    }

    /**
     * Send a 401 Unauthorized response.
     *
     * @param string|null $message Optional error message.
     */
    public function respondUnauthorized(?string $message = null): Response
    {
        return $this->respondError($message ?? 'Unauthorized.', 401);
    }

    /**
     * Send a 403 Forbidden response.
     *
     * @param string|null $message Optional error message.
     */
    public function respondForbidden(?string $message = null): Response
    {
        return $this->respondError($message ?? 'Forbidden.', 403);
    }

    /**
     * Send a 400 Bad Request response.
     *
     * @param string|null $message Optional error message.
     * @param array $errors Optional detailed error map.
     */
    public function respondBadRequest(?string $message = null, array $errors = []): Response
    {
        return $this->respondError($message ?? 'Bad request.', 400, $errors);
    }

    /**
     * Send a 422 Unprocessable Entity response for validation errors.
     *
     * @param array $errors Validation error details.
     * @param string|null $message Optional error message.
     */
    public function respondValidationError(array $errors, ?string $message = null): Response
    {
        return $this->respondError($message ?? 'Validation failed.', 422, $errors);
    }

    /**
     * Send a 202 Accepted response for async processing.
     *
     * @param mixed $data Response payload.
     * @param string|null $message Optional human-readable message.
     */
    public function respondAccepted($data, ?string $message = null): Response
    {
        return $this->respondSuccess($data, $message, 202);
    }

    /**
     * Send a generic error response.
     *
     * @param string|null $message Error message.
     * @param int $status HTTP status code (default: 500).
     * @param array $errors Optional detailed error map.
     */
    public function respondError(?string $message = null, int $status = 500, array $errors = []): Response
    {
        return $this->respond(
            false,
            null,
            $message ?? 'An error occurred.',
            $errors ?: null,
            $status
        );
    }

    /**
     * Build the standard API envelope and encode it as JSON.
     */
    protected function respond(bool $success, $data, ?string $message, ?array $errors, int $status): Response
    {
        $payload = [
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ];

        return response()->setStatus($status)->json($this->filterNulls($payload));
    }

    /**
     * Remove null values from the envelope so the response stays clean.
     */
    protected function filterNulls(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null);
    }
}
