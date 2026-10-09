Feature: Master Scope Guard with Partner Key
  As an API client with a valid partner key
  I want to attempt a master-only operation (like creating a partner)
  So that the system returns 403 Forbidden instead of 401 Unauthorized

  Scenario: Valid partner key attempting to create a partner returns 403 Forbidden
    Given a valid Partner and an active API key
    When I send a POST request to "/partners" with the partner's API key
    Then the response status code should be 403 Forbidden

  Scenario: Valid partner key attempting to patch a partner returns 403 Forbidden
    Given a valid Partner with an active API key
    When I send a PATCH request to "/partners/{id}" with the partner's API key
    Then the response status code should be 403 Forbidden
