Feature: Contractor Entity Direct Migration
  As an API client
  I want to manage Contractors directly as API Resources
  So that validation, persistence, and serialization follow entity direct standards

  Scenario: Create a contractor under a partner
    Given a valid Partner with ID "prt_1234567890abcdef"
    When I send a POST request to "/partners/prt_1234567890abcdef/contractors" with name "Acme Corp" and document "123456789"
    Then the response status code should be 201 Created
    And the response should contain contractor data with group "contractor:get"

  Scenario: Fail on duplicate document
    Given an existing Contractor with document "123456789"
    When I send a POST request to create another contractor with the same document
    Then the response status code should be 409 Conflict
