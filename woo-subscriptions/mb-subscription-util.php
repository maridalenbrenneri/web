<?php

class mb_subscription_util {

    /*
    * Returns the date of the day of first or third $dayOfWeek
    * Defaults to monthly (first week only)
    */
    function getNextRenewalDate($isFortnightly = false, $dayOfWeek = 'monday') {
        $today = new DateTime();
        date_time_set($today, 0, 0);

        $date = new DateTime('first ' . $dayOfWeek . ' of this month');

        if($date <= $today && $isFortnightly) {
            $date = new DateTime('third ' . $dayOfWeek . ' of this month');
        }

        if($date <= $today) {
            $date = new DateTime('first ' . $dayOfWeek . ' of next month');
        }

        return $date;
    }
}


$date_util = new mb_subscription_util();
echo 'Next monthly date: ' . $date_util->getNextRenewalDate()->format('l F d, Y');
echo '<br><br>';
echo 'Next fortnightly date: ' . $date_util->getNextRenewalDate(true)->format('l F d, Y');

?>

