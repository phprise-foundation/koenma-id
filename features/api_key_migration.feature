Feature: ApiKey Entity Direct Migration
  As a project owner
  I want to issue and revoke ApiKeys via direct entity API resources
  So that raw security keys are displayed only once upon creation

  Scenario: Issue API Key for a project
    Given a valid Project with ID "prj_1234567890abcdef"
    When I send a POST request to "/projects/prj_1234567890abcdef/api-keys" with name "Production Key"
    Then the response status code should be 201 Created
    And the response contains the raw securityKey in group "api_key:post" once
    And subsequent GET requests do not reveal the raw key
