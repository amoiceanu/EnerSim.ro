<?php

return [
    'frigider' => ['name' => 'Frigider', 'icon' => '❄️', 'category' => 'refrigerare', 'nominal_power_w' => 120, 'startup_power_w' => 700, 'hours_per_day' => 8, 'priority' => 1, 'is_essential' => true],
    'lada-frigorifica' => ['name' => 'Ladă frigorifică', 'icon' => '🧊', 'category' => 'refrigerare', 'nominal_power_w' => 150, 'startup_power_w' => 850, 'hours_per_day' => 10, 'priority' => 1, 'is_essential' => true],
    'iluminat' => ['name' => 'Iluminat', 'icon' => '💡', 'category' => 'casa', 'nominal_power_w' => 100, 'startup_power_w' => 0, 'hours_per_day' => 6, 'priority' => 2, 'is_essential' => true],
    'router' => ['name' => 'Router & internet', 'aliases' => ['Router'], 'icon' => '📶', 'category' => 'electronice', 'nominal_power_w' => 25, 'startup_power_w' => 0, 'hours_per_day' => 24, 'priority' => 1, 'is_essential' => true],
    'televizor' => ['name' => 'Televizor', 'aliases' => ['TV'], 'icon' => '📺', 'category' => 'electronice', 'nominal_power_w' => 140, 'startup_power_w' => 0, 'hours_per_day' => 5, 'priority' => 3, 'is_essential' => false],
    'calculator' => ['name' => 'Calculator', 'aliases' => ['PC'], 'icon' => '🖥️', 'category' => 'electronice', 'nominal_power_w' => 350, 'startup_power_w' => 0, 'hours_per_day' => 8, 'priority' => 2, 'is_essential' => false],
    'hidrofor' => ['name' => 'Hidrofor', 'icon' => '💧', 'category' => 'pompe', 'nominal_power_w' => 900, 'startup_power_w' => 2500, 'hours_per_day' => .5, 'priority' => 1, 'is_essential' => true],
    'pompa-apa' => ['name' => 'Pompă de apă', 'icon' => '🚰', 'category' => 'pompe', 'nominal_power_w' => 1100, 'startup_power_w' => 3000, 'hours_per_day' => 1.5, 'priority' => 2, 'is_essential' => false],
    'centrala-lemne' => ['name' => 'Centrală pe lemne', 'icon' => '🔥', 'category' => 'incalzire', 'nominal_power_w' => 150, 'startup_power_w' => 0, 'hours_per_day' => 24, 'priority' => 1, 'is_essential' => true],
    'boiler' => ['name' => 'Boiler electric', 'aliases' => ['Boiler'], 'icon' => '🔥', 'category' => 'incalzire', 'nominal_power_w' => 2000, 'startup_power_w' => 0, 'hours_per_day' => 2, 'priority' => 3, 'is_essential' => false],
    'pompa-caldura' => ['name' => 'Pompă de căldură', 'icon' => '♨️', 'category' => 'incalzire', 'nominal_power_w' => 2800, 'startup_power_w' => 4200, 'hours_per_day' => 7, 'priority' => 2, 'is_essential' => false],
    'aer-conditionat' => ['name' => 'Aer condiționat', 'icon' => '🌬️', 'category' => 'climatizare', 'nominal_power_w' => 1200, 'startup_power_w' => 1800, 'hours_per_day' => 5, 'priority' => 2, 'is_essential' => false],
    'masina-spalat' => ['name' => 'Mașină de spălat', 'icon' => '🫧', 'category' => 'electrocasnice', 'nominal_power_w' => 1800, 'startup_power_w' => 2100, 'hours_per_day' => 1, 'priority' => 3, 'is_essential' => false],
    'uscator-rufe' => ['name' => 'Uscător de rufe', 'icon' => '👕', 'category' => 'electrocasnice', 'nominal_power_w' => 2400, 'startup_power_w' => 0, 'hours_per_day' => 1.5, 'priority' => 3, 'is_essential' => false],
    'masina-vase' => ['name' => 'Mașină de spălat vase', 'icon' => '🍽️', 'category' => 'electrocasnice', 'nominal_power_w' => 1800, 'startup_power_w' => 0, 'hours_per_day' => 1.5, 'priority' => 3, 'is_essential' => false],
    'cuptor-electric' => ['name' => 'Cuptor electric', 'icon' => '♨', 'category' => 'bucatarie', 'nominal_power_w' => 2500, 'startup_power_w' => 0, 'hours_per_day' => 1, 'priority' => 3, 'is_essential' => false],
    'plita-inductie' => ['name' => 'Plită cu inducție', 'icon' => '🍳', 'category' => 'bucatarie', 'nominal_power_w' => 3500, 'startup_power_w' => 0, 'hours_per_day' => 1.5, 'priority' => 3, 'is_essential' => false],
    'masina-electrica' => ['name' => 'Mașină electrică', 'icon' => '🚗', 'category' => 'mobilitate', 'nominal_power_w' => 7400, 'startup_power_w' => 0, 'hours_per_day' => 3, 'priority' => 3, 'is_essential' => false],
    'masina-phev' => ['name' => 'Mașină PHEV', 'aliases' => ['Automobil plug-in hybrid'], 'icon' => '🚙', 'category' => 'mobilitate', 'nominal_power_w' => 3700, 'startup_power_w' => 0, 'hours_per_day' => 2.5, 'priority' => 3, 'is_essential' => false],
    'triciclu-electric' => ['name' => 'Triciclu electric', 'icon' => '🛺', 'category' => 'mobilitate', 'nominal_power_w' => 800, 'startup_power_w' => 0, 'hours_per_day' => 4, 'priority' => 3, 'is_essential' => false],
];
