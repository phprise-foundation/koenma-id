Feature: Project Subresource Management
  As an API client
  I want to create and manage Projects under a Partner
  So that projects are correctly scoped and isolated

  Scenario: Create a project under a valid partner
    Given a valid Partner with ID "prt_1234567890abcdef"
    When I send a POST request to "/partners/prt_1234567890abcdef/projects" with name "New Project"
    Then the response status code should be 201 Created
    And the response should contain project data with group "project:get"

  Scenario: Fail to create a project under a non-existent partner
    When I send a POST request to "/partners/prt_nonexistent/projects" with name "Orphan Project"
    Then the response status code should be 404 Not Found
