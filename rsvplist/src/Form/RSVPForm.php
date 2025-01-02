<?php

/**
 * @file
 * A form to collect an email address for RSVP details.
 */

 namespace Drupal\rsvplist\Form;

 use Drupal\Core\Form\FormBase; 
 use Drupal\Core\Form\FormInterface;
 use Drupal\Core\Form\FormStateInterface;

 class RSVPForm extends FormBase{

    /**
     * {@inheritdoc}
     */
    public function getFormId(){
        return 'rsvplist_email_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state){

        //Attempt to get a fully loaded node object of viewed page.
        $node = \Drupal::routeMatch()->getParameter('node');

        // Some pages may not be nodes though and $node will be NULL on those pages.
        // If the node was loaded, get the node ID.
        if(!is_null($node)){
            $nid = $node->id();
        }
        else{
            // If a node could not be loaded, default to 0.
            $nid = 0;
        }


        // Establish the $form render array. It has an email text field,
        // a submit button, and a hidden field containing node ID.
        $form['email'] = [
            '#type' => 'textfield',
            '#title' => t('Email Address'),
            '#size' => 25,
            '#description' => t('We will send updates to the email address you 
              provide.'),
            '#required' => TRUE,
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => t('RSVP'),
        ];
        $form['nid'] = [
            '#type' => 'hidden',
            '#value' => $nid,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $value = $form_state->getValue('email');
        if(!(\Drupal::service('email.validator')->isvalid($value))){
            $form_state->setErrorByName('email',
              $this->t('It appears that %email is not a valid email. Please
                try again',['%email' => $value]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state){
        // $submitted_email = $form_state->getValue('email');
        // $this->messenger()->addMessage(t("The form is working! You entered @entry.",
        //   ['@entry'=> $submitted_email]));

        try{
            // Begin Phase 1:initiate variables to save

            // Get current user ID
            $uid = \Drupal::currentUser()->id();

            // this is just for info, this gives a full user object
            $full_user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

            // Obtain values as entered into form.
            $nid = $form_state->getValue('nid');
            $mail = $form_state->getValue('email');

            $current_time = \Drupal::time()->getRequestTime();
            //End Phase 1


            // Begin Phase 2:Save values to database

            // Start to build a query builder object $query
            $query = \Drupal::database()->insert('rsvplist');

            // Specify the field that query will insert into
            $query->fields([
                'uid',
                'nid',
                'mail',
                'created',
            ]);

            // Set the values of fields we selected above
            // Note that order should be same as above
            $query->values([
                $uid,
                $nid,
                $mail,
                $current_time,
            ]);

            //Execute query
            $query->execute();
            //End Phase 2


            // Begin Phase 3: Dsiplay a success message

            //display simple message
            \Drupal::messenger()->addMessage(
                t('Thank you for your RSVP, you are on the list for the event.')
            );
            //End Phase 3
        }
        catch(\Exception $e){
            \Drupal::messenger()->addError(
                t('Unable to save RSVP settings at this time due to database error.
                    Please try again!!')
            );
        }
    }
 }