<?php

if ( ! defined( 'WPINC' ) ) exit;

new Dy_Core_Fields('aircrafts', []);

function aviation_field(string $name, null|int $the_id = null) : string {

    try {

        if ($the_id === null) {
            $the_id = get_dy_id();

            if($the_id === null) {
                $request_uri = secure_server('REQUEST_URI');
                throw new Exception("'the_id' can not be null if 'post' is undefined in package_field(): $name, URL: $request_uri");
            }
        }

        return Dy_Core_Fields::get($name, $the_id);

    } catch (Throwable $e) {
        write_log(
            [
                'message'   => 'Dy_Core_Fields::get() failed.',
                'exception' => get_class($e),
                'error'     => $e->getMessage()
            ],
            true,
            false,
            'ERROR'
        );

        return '';
    }
}

?>
