<?php

return [
    // React imports route() from ziggy-js, so @routes only emits the route list.
    'skip-route-function' => true,

    // Framework internals the browser never needs to know about.
    'except' => ['ignition.*', 'sanctum.*', 'storage.*', 'livewire.*'],
];
