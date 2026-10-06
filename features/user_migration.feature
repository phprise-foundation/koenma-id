Feature: User Entity Direct Migration
  As an API client with a security key
  I want to register Users linked to Contractors using entity direct resources
  So that user credentials are securely stored and validated

  Scenario: Register user under a contractor using security key
    Given a valid ApiKey and Contractor "cnt_1234567890abcdef"
    When I send a POST request to "/contractors/cnt_1234567890abcdef/users" with valid payload and X-Security-Key header
    Then the response status code should be 201 Created
    And the response should contain user data with group "user:get" without password hash
