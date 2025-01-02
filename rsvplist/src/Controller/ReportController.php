<?php

/**
 * @file
 * Provides site administrators with a list of all the RSVP signups
 * so they know who is attending their events.
 */

 namespace Drupal\rsvplist\Controller;

 use Drupal\Core\Controller\ControllerBase;
 use Drupal\Core\Database\Database;

 class ReportController extends ControllerBase{

    /**
     * Get and returns all RSVPs for all nodes.
     * These are returned as an associative array, with each row
     * containing the username, the node title, and email of RSVP.
     * 
     * @return array|null
     */
    protected function load(){
        try{
            $database = \Drupal::database();
            $select_query = $database->select('rsvplist','r');

            //Join the user table, so we can get the entry creator's username
            $select_query->join('users_field_data', 'u', 'r.uid = u.uid');
            //Join the node table, so we can get the event's name.
            $select_query->join('node_field_data', 'n', 'r.nid = n.nid');

            // select these specific fields for the output.
            $select_query->addField('u', 'name', 'username');
            $select_query->addField('n', 'title');
            $select_query->addField('r', 'mail');

            $entries = $select_query->execute()->fetchAll(\PDO::FETCH_ASSOC);

            // Return the associative array of RSVPList entries.
            return $entries;
        }
        catch(\Exception $e){
            \Drupal::messenger()->addStatus(
                t('Unable to access the database at the time. Please try again later.')
            );
            \Drupal::logger('rsvplist')->error('Error loading RSVPs: @message', ['@message' => $e->getMessage()]);
            return NULL;
        }
    }

    /**
     * Create the RSVPList report page.
     * 
     * @return array
     * Reader array for the RSVPList report output.
     */
    public function report(){
        $content = [];

        $content['message'] = [
            '#markup' => t('Below is a list of all Event RSVPs including username,
                        email address and name of the event they will be attending.'),
        ];

        $headers = [
            t('Username'),
            t('Event'),
            t('Email'),
        ];

        // Because load() returns an associative array with each table row
        // as its own array, we can simply define the HTML table rows like this:
        $table_rows = $this->load();

        // Create a render array for rendering an HTML table.
        $content['table'] = [
            '#type' => 'table',
            '#header' => $headers,
            '#rows' => $table_rows,
            '#empty' => t('No entries available.'),
        ];

        // Do not cache this page by setting the max-age to 0.
        $content['#cache']['max-age'] = 0;

        // Return the populated render array
        return $content;
    }
 }