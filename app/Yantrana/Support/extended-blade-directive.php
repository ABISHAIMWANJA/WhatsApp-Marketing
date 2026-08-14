<?php
/**
 * WhatsJet
 *
 * This file is part of the WhatsJet software package developed and licensed by livelyworks.
 *
 * You must have a valid license to use this software.
 *
 * © 2024 - 2026 livelyworks. All rights reserved.
 * Redistribution or resale of this file, in whole or in part, is prohibited without prior written permission from the author.
 *
 * For support or inquiries, contact: contact@livelyworks.net
 *
 * @package     WhatsJet
 * @author      livelyworks <contact@livelyworks.net>
 * @copyright   Copyright (c) 2024 - 2026 livelyworks
 * @website     https://livelyworks.net
 */

/**
 * Custom blade directive for checkbox
 *
 * @return bool
 *---------------------------------------------------------------- */
Blade::directive('lwCheckboxField', function ($expression) {
    $parameters = explode(', ', $expression);
    $name = array_get($parameters, '0');
    $label = array_get($parameters, '1');
    $value = array_get($parameters, '2');
    $id = array_get($parameters, '3');
    $checkString = ($value == 'true') ? 'checked' : '';

    return <<<EOL
    <?php
 echo "<input type='hidden' name=e($name) value='false'>" ?>
 echo "<div class='custom-control custom-checkbox custom-control-inline'>"; ?>
<?php
 echo "<input type='checkbox' name=$name value='true' class='custom-control-input' id=$id $checkString>"; ?>
    <?php
 echo "<label class='custom-control-label' for=$id>$label</label>"; ?>
    <?php
 echo "</div>"; ?>
EOL;
});

// use it so the push will only work on non ajax request
Blade::directive('lwPush', function ($expression) {
return <<<EOL
    <?php if(!request()->ajax()): \$__env->startPush($expression); endif; ?>
EOL;
});
Blade::directive('lwPushEnd', function ($expression) {
    return <<<EOL
    <?php if(!request()->ajax()): \$__env->stopPush(); endif; ?>
    EOL;
});

// Use it to provide json data to javascript etc eg. x-data=@lwJson
Blade::directive('lwJson', function ($expression) {
    return <<<EOL
    <?php echo htmlentities(json_encode($expression)); ?>
    EOL;
});