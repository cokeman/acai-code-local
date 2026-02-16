<?php
abstract class IPNAction {
    /**
     * Ejecuta una acción al realizarse un pago correcto en el ipn
     *
     * @param array $payment
     * @return void
     */
    abstract function performAction($payment);

    abstract function performCancel($payment, $message);

    /**
     * Devuelve el payment asignado al id
     *
     * @param string $id
     * @return void
     */
    static function get($id) {
        $id = mysql_real_escape_string($id);
        $record = mysql_fetch_assoc(mysql_query("SELECT * FROM `aux_plg_payments` WHERE SHA1(num)='$id'"));
        if (!$record) {
            throw new Exception('No se ha encontrado el pago');
        }
        return $record;
    }

    static function get_num_by_subscription($subscription) {
        $subscription = mysql_real_escape_string($subscription);
        $record = mysql_fetch_assoc(mysql_query("SELECT * FROM `aux_plg_payments` WHERE card_id='$subscription'"));
        if (!$record) {
            throw new Exception('No se ha encontrado el pago por suscripción');
        }
        return sha1($record['num']);
    }
}