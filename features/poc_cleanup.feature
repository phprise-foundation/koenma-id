Feature: POC Cleanup
  As a developer
  I want to remove temporary POC artifacts like Gadget, Widget, and SubresourceProbeTest
  So that the codebase remains clean and production-ready

  Scenario: Verify absence of POC endpoints and classes
    When I check for Gadget or Widget classes or routes
    Then they should not exist in the codebase
    And test suite should not reference them
