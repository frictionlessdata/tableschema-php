Feature: Schema object

  Scenario: initializing from valid descriptors
    Given a list of valid descriptors
    When initializing the Schema object with the descriptors
    Then object should be initialized without exceptions
