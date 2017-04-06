Feature: Schema object

  Scenario: initializing from valid descriptors
    the list of valid descriptors load from pure php, json strings and loading from a file
    no need to test loading from url because we rely on the php file_get_contents function to handle it
    Given a list of valid descriptors
    When initializing the Schema object with the descriptors
    Then all schemas should be initialized without exceptions and return the expected descriptor

  Scenario: initializing from invalid descriptors
    Given a list of invalid descriptors
    When initializing the Schema object with the descriptors
    Then all schemas should raise an exception

  Scenario: validating descriptors
    this list of descriptors includes the expected validation errors for each descriptor
    Given a list of invalid descriptors
    When validating the descriptors
    Then validation results should be as expected
