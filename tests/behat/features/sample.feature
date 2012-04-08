Feature: Test the Sample application
Scenario Outline: Test the greeting
    Given that an instance exists
    And its name is "<name>"
    And make it greet me
    Then it should say "Hello, Danny"

    Examples:
        |   name  |
        |  Danny  |
        |  Sample |