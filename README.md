# enrol_payment
This Moodle plugin is a superset of Moodle's **enrol\_paypal** functionality. Please note that a public URL is needed.

## Features
- Use Stripe or PayPal payment backend
- Define discount codes with percentage-based or amount-based discounts (see **Discounts**, below)
- Allow users to enrol other users (see **Multiple Enrolment**, below)
- Define custom tax rules (see **Adding a Province/State Field**, below)

## Discounts
To set up a discount code:
- Ensure that "Allow course enrolment to include a discount" is checked in the site-wide plugin settings.
- In the settings for an individual enrolment instance, select a discount type and enter the corresponding 
discount amount and discount code.

## Multiple Enrolment
To enable the Multiple Enrolment system:
- In the site-wide plugin settings, check "Allow multiple registration".
- In the settings for an individual enrolment instance, check "Allow multiple registration".

This will allow users to enroll 1 or more other users in the course, provided the users' email addressses 
are known.

## Adding a Province/State Field
Since Moodle does not natively collect province/state info, the instructions
below are hacks to core files to enable the insertion of a province/state code
in the “msn” field in the user table. This value is use by the plugin to
calculate tax.


### STEP 1: Customize the following language strings in the core moodle.php
**msnid:** Province/state
**state:** Select province/state
**missingreqreason**: Missing province/state (NB: needed to make “msn” [a.k.a. the state/province field] a
required field)

### STEP 2: Create the states file
Name your state/province file states.php and upload it in the folder /lang/en.
This file needs to begin with `<?PHP` and end with `?>` and have as many entries in
the format: `$string['Code']` = 'State'; as required. For example:

```PHP
<?PHP
$string['AB'] = 'Alberta';
.
.
.
$string['WY'] = 'Wyoming';
?>
```

### STEP 3: Add function get_list_of_states()
In `/lib/classes/string_manager_standard.php`, just above the `get_list_of_countries()` function, insert:

```PHP
    /**
     * Returns a localised list of all state names, sorted by localised name.
     * @return array two-letter state code => translated name.
     */
    public function get_list_of_states($returnall = false, $lang = NULL) {
        global $CFG;
        if ($lang === NULL) {
            $lang = current_language();
        }
        $states = $this->load_component_strings('core_states', $lang);
        if (!$returnall and !empty($CFG->allstatecodes)) {
            $enabled = explode(',', $CFG->allstatecodes);
            $return = array();
            foreach ($enabled as $c) {
                if (isset($states[$c])) {
                    $return[$c] = $states[$c];
                }
            }
            return $return;
        }
        return $states;
    }
```

In `/lib/classes/string_manager.php`, just above the `get_list_of_countries()` function, insert:
```PHP
     /**
     * Returns a localised list of all state names, sorted by localised name.
     * @return array two-letter state code => translated name.
     */
    public function get_list_of_states($returnall = false, $lang = null);
```

### STEP 4: Modify the signup form
In `login/signup_form.php`, above the country code insert:

```PHP
$state = get_string_manager()->get_list_of_states();
$default_state[''] = get_string('state');
$state = array_merge($default_state, $state);
$mform->addElement('select', 'msn', get_string('msnid'), $state);
$mform->addRule('msn', get_string('missingreqreason'), 'required', null, 'server');
```

### STEP 5: Modify the edit profile form
In `user/editlib.php`, above this line:
`$choices = get_string_manager()->get_list_of_countries();`
insert:

```PHP
$choices = get_string_manager()->get_list_of_states();
$choices= array(''=>get_string('state') . '...') + $choices;
$mform->addElement('select', 'msn', get_string('msnid'), $choices);
$mform->addRule('msn', get_string('missingreqreason'), 'required', null, 'server');
```

Ensure to comment out the “msn” field from the moodle optional fields.
