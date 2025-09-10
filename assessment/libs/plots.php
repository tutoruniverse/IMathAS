<?php
global $allowedmacros;
$allowedmacros[] = "plot_polar";
$allowedmacros[] = "plot_multiple_implicit";

/**
 * Plots one or more polar functions r = f(θ).
 *
 * @param array $functions      An array of functions to plot. ['function' => callable(theta)]
 * @param array $global_options An array of global options for the entire plot.
 * @return string               The complete SVG string.
 * 
 */

/*
* 1. Define the array of functions to plot
$polar_functions = [
    [
        'function' => function($theta) {
            return 0.5 * $theta; // Archimedean Spiral
        },
        'plotColor' => '#e67e22',
        'plotWidth' => 2.5
    ],
    [
        'function' => function($theta) {
            return 4 * cos(2 * $theta); // 4-petal Rose Curve
        },
        'plotColor' => '#8e44ad',
        'plotWidth' => 3
    ]
];

// 2. Define custom labels
$custom_labels = [
    [
        'x' => 0,
        'y' => 0,
        'text' => 'r = 4cos(2θ)',
        'color' => '#8e44ad',
        'fontSize' => 14
    ],
    [
        'x' => 4,
        'y' => -5,
        'text' => 'r = 0.5θ',
        'color' => '#e67e22',
        'fontSize' => 14,
        'anchor' => 'end'
    ]
];


// 3. Define global options
$global_options = [
    'r_max' => 7,
    'theta_max' => 6 * M_PI, // Draw 3 rotations to make the spiral longer
    'imageWidth' => 600,
    'imageHeight' => 600,
    'num_points' => 1000,
    'labels' => $custom_labels,
    'concentric_circles' => 7, // 7 circles, each 1 unit apart
    'radial_lines' => 12 // 12 angle lines, each 30 degrees apart
];

// 4. Call the function and print the SVG
echo plot_polar($polar_functions, $global_options);
*/

function plot_polar(array $functions, array $global_options = []): string
{
    // --- 1. HANDLE OPTIONS AND DEFAULT VALUES ---
    $defaults = [
        'r_max' => 5, // Maximum radius of the plot viewport
        'theta_min' => 0,
        'theta_max' => 4 * M_PI, // Default to drawing 2 full rotations
        'num_points' => 1000, // Number of points to generate the curve, more points = smoother curve
        'imageWidth' => 500, 'imageHeight' => 500,
        'plotColor' => 'blue', 'plotWidth' => 2,
        'bgColor' => '#ffffff',
        // Polar Grid Options
        'showPolarGrid' => true,
        'gridColor' => '#ced4da',
        'radial_lines' => 12, // Number of radial lines for angles (e.g., 12 lines = 30 deg/line)
        'concentric_circles' => 5, // Number of concentric circles for radius
        // Label Options
        'showAxisLabels' => true,
        'axisLabelColor' => '#495057',
        'axisLabelFontSize' => 10,
        'labels' => [],
    ];
    $config = array_merge($defaults, $global_options);

    // --- 2. INITIALIZE SVG AND COORDINATE SYSTEM ---
    // The coordinate window will be [-r_max, r_max] for both x and y
    $range = 2 * $config['r_max'];
    $scale = $config['imageWidth'] / $range;
    
    $svg = sprintf(
        '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" style="background-color:%s; font-family: sans-serif;">',
        $config['imageWidth'], $config['imageHeight'], $config['bgColor']
    );
    // Move the origin to the center and invert the Y-axis
    $svg .= sprintf('<g transform="translate(%f, %f) scale(%f, %f)">',
        $config['imageWidth']/2, $config['imageHeight']/2, $scale, -$scale
    );

    // --- 3. DRAW THE POLAR GRID ---
    if ($config['showPolarGrid']) {
        // Draw concentric circles
        $r_step = $config['r_max'] / $config['concentric_circles'];
        for ($i = 1; $i <= $config['concentric_circles']; $i++) {
            $r = $i * $r_step;
            $svg .= sprintf('<circle cx="0" cy="0" r="%f" fill="none" stroke="%s" stroke-width="%f"/>',
                $r, $config['gridColor'], 1 / $scale
            );
        }
        // Draw radial lines
        $theta_step = 2 * M_PI / $config['radial_lines'];
        for ($i = 0; $i < $config['radial_lines']; $i++) {
            $theta = $i * $theta_step;
            $x2 = $config['r_max'] * cos($theta);
            $y2 = $config['r_max'] * sin($theta);
            $svg .= sprintf('<line x1="0" y1="0" x2="%f" y2="%f" stroke="%s" stroke-width="%f"/>',
                $x2, $y2, $config['gridColor'], 1 / $scale
            );
        }
        // Main X and Y axes
        $svg .= sprintf('<line x1="%f" y1="0" x2="%f" y2="0" stroke="%s" stroke-width="%f"/>', -$config['r_max'], $config['r_max'], $config['gridColor'], 1.5 / $scale);
        $svg .= sprintf('<line x1="0" y1="%f" x2="0" y2="%f" stroke="%s" stroke-width="%f"/>', -$config['r_max'], $config['r_max'], $config['gridColor'], 1.5 / $scale);
    }
    
    // --- 4. PLOT THE FUNCTIONS ---
    $d_theta = ($config['theta_max'] - $config['theta_min']) / $config['num_points'];
    foreach ($functions as $func_item) {
        if (!isset($func_item['function']) || !is_callable($func_item['function'])) continue;
        
        $plot_config = array_merge($config, $func_item);
        $function = $plot_config['function'];
        
        $points = [];
        for ($i = 0; $i <= $plot_config['num_points']; $i++) {
            $theta = $plot_config['theta_min'] + $i * $d_theta;
            $r = $function($theta);
            
            // Skip invalid values
            if (!is_numeric($r)) continue;

            $x = $r * cos($theta);
            $y = $r * sin($theta);
            $points[] = "$x,$y";
        }
        
        $svg .= sprintf('<polyline points="%s" fill="none" stroke="%s" stroke-width="%f" stroke-linecap="round" stroke-linejoin="round"/>',
            implode(' ', $points),
            $plot_config['plotColor'],
            $plot_config['plotWidth'] / $scale
        );
    }
    
    // --- 5. DRAW LABELS (SCALE AND CUSTOM) ---
    // Radius labels on the positive X-axis
    if ($config['showAxisLabels']) {
        $font_size_scaled = $config['axisLabelFontSize'] / $scale;
        for ($i = 1; $i <= $config['concentric_circles']; $i++) {
            $r = $i * $r_step;
            $svg .= sprintf(
                '<text x="%f" y="%f" transform="scale(1, -1)" fill="%s" font-size="%fpx" text-anchor="middle" dominant-baseline="hanging">%g</text>',
                $r, -$font_size_scaled*1.2, $config['axisLabelColor'], $font_size_scaled, $r
            );
        }
    }
    // Custom labels
    foreach ($config['labels'] as $label) {
        $x = $label['x'] ?? 0; $y = $label['y'] ?? 0; $text = $label['text'] ?? '';
        $color = $label['color'] ?? '#000'; $fontSize = $label['fontSize'] ?? 12;
        $anchor = $label['anchor'] ?? 'middle';
        $font_size_scaled = $fontSize / $scale;
        $svg .= sprintf(
            '<text x="%f" y="%f" transform="scale(1, -1)" fill="%s" font-size="%fpx" text-anchor="%s" dominant-baseline="middle">%s</text>',
            $x, -$y, $color, $font_size_scaled, $anchor, htmlspecialchars($text)
        );
    }

    // --- 6. FINISH THE SVG ---
    $svg .= '</g></svg>';
    return $svg;
}

/**
 * Plots ONE OR MORE implicit functions, with support for scales and custom labels.
 *
 * @param array $functions      Array of functions to plot.
 * @param array $global_options Array of global options for the entire plot.
 * @return string               The complete SVG string.
 */

/*
$heart_curve = [
    [
        'function' => function($x, $y) {
            $term1 = $x*$x + $y*$y - 1;
            return pow($term1, 3) - ($x*$x * pow($y, 3));
        },
        'plotColor' => '#e91e63',
        'plotWidth' => 3
    ]
];

$custom_labels = [
    [
        'x' => 0,
        'y' => 0,
        'text' => 'Heart Curve ❤️',
        'color' => '#d63384',
        'fontSize' => 16,
        'anchor' => 'middle'
    ],
    [
        'x' => 1.5,
        'y' => 1.5,
        'text' => '(x²+y²-1)³ - x²y³ = 0',
        'color' => '#666',
        'fontSize' => 11,
        'anchor' => 'end'
    ]
];

$global_options = [
    'xmin' => -1.8, 'xmax' => 1.8,
    'ymin' => -1.5, 'ymax' => 2,
    'imageWidth' => 600, 'imageHeight' => 600,
    'resolution' => 300,
    'gridStepX' => 0.5,
    'gridStepY' => 0.5,
    'showAxisLabels' => true,
    'labels' => $custom_labels
];

echo plot_multiple_implicit($heart_curve, $global_options);
*/
function plot_multiple_implicit(array $functions, array $global_options = []): string
{
    // --- 1. HANDLE GLOBAL OPTIONS ---
    $defaults = [
        'xmin' => -5, 'xmax' => 5, 'ymin' => -5, 'ymax' => 5,
        'imageWidth' => 500, 'imageHeight' => 500,
        'resolution' => 200,
        'plotColor' => 'blue', 'plotWidth' => 2,
        'bgColor' => '#ffffff',
        'showAxes' => true, 'axesColor' => '#343a40',
        'showGrid' => true, 'gridColor' => '#e9ecef',
        'gridStepX' => 1, 'gridStepY' => 1,
        // --- Axis Labels Options ---
        'showAxisLabels' => true,
        'axisLabelColor' => '#495057',
        'axisLabelFontSize' => 10, // Font size in pixels
        // --- Custom Labels ---
        'labels' => [], // Array of label objects
    ];
    $global_config = array_merge($defaults, $global_options);

    $x_range = $global_config['xmax'] - $global_config['xmin'];
    $y_range = $global_config['ymax'] - $global_config['ymin'];
    $step_x = $x_range / $global_config['resolution'];
    $step_y = $y_range / $global_config['resolution'];

    // --- 2. INITIALIZE SVG AND COORDINATE SYSTEM ---
    $svg = sprintf(
        '<svg width="%d" height="%d" xmlns="http://www.w3.org/2000/svg" style="background-color:%s; font-family: sans-serif;">',
        $global_config['imageWidth'], $global_config['imageHeight'], $global_config['bgColor']
    );

    $scaleX = $global_config['imageWidth'] / $x_range;
    $scaleY = $global_config['imageHeight'] / $y_range;
    $translateX = -$global_config['xmin'] * $scaleX;
    $translateY = $global_config['ymax'] * $scaleY;

    $svg .= sprintf('<g transform="translate(%f, %f) scale(%f, %f)">', $translateX, $translateY, $scaleX, -$scaleY);

    // Draw grid and axes
    if ($global_config['showGrid']) { // Grid
        for ($x = ceil($global_config['xmin'] / $global_config['gridStepX']) * $global_config['gridStepX']; $x <= $global_config['xmax']; $x += $global_config['gridStepX']) {
            $svg .= sprintf('<line x1="%f" y1="%f" x2="%f" y2="%f" stroke="%s" stroke-width="%f"/>', $x, $global_config['ymin'], $x, $global_config['ymax'], $global_config['gridColor'], 0.5 / $scaleY);
        }
        for ($y = ceil($global_config['ymin'] / $global_config['gridStepY']) * $global_config['gridStepY']; $y <= $global_config['ymax']; $y += $global_config['gridStepY']) {
            $svg .= sprintf('<line x1="%f" y1="%f" x2="%f" y2="%f" stroke="%s" stroke-width="%f"/>', $global_config['xmin'], $y, $global_config['xmax'], $y, $global_config['gridColor'], 0.5 / $scaleX);
        }
    }
    if ($global_config['showAxes']) { // Axes
        $svg .= sprintf('<line x1="%f" y1="0" x2="%f" y2="0" stroke="%s" stroke-width="%f"/>', $global_config['xmin'], $global_config['xmax'], $global_config['axesColor'], 1 / $scaleY);
        $svg .= sprintf('<line x1="0" y1="%f" x2="0" y2="%f" stroke="%s" stroke-width="%f"/>', $global_config['ymin'], $global_config['ymax'], $global_config['axesColor'], 1 / $scaleX);
    }
    
    // --- LOOP THROUGH EACH FUNCTION TO PLOT ---
    foreach ($functions as $func_item) {
        if (!isset($func_item['function']) || !is_callable($func_item['function'])) { continue; }
        $function = $func_item['function'];
        $config = array_merge($global_config, $func_item);
        $grid_values = [];
        for ($i = 0; $i <= $config['resolution']; $i++) {
            for ($j = 0; $j <= $config['resolution']; $j++) {
                $x = $config['xmin'] + $i * $step_x;
                $y = $config['ymin'] + $j * $step_y;
                $grid_values[$i][$j] = $function($x, $y);
            }
        }
        $path_data = '';
        for ($i = 0; $i < $config['resolution']; $i++) {
            for ($j = 0; $j < $config['resolution']; $j++) {
                $x = $config['xmin'] + $i * $step_x;
                $y = $config['ymin'] + $j * $step_y;
                $v = [$grid_values[$i][$j], $grid_values[$i+1][$j], $grid_values[$i+1][$j+1], $grid_values[$i][$j+1]];
                $case = ($v[0]>0) | (($v[1]>0)<<1) | (($v[2]>0)<<2) | (($v[3]>0)<<3);
                $interpolate = fn($p1, $p2, $v1, $v2) => $p1 + ($p2 - $p1) * (-$v1) / ($v2 - $v1);
                $p = [['x'=>$x, 'y'=>$y], ['x'=>$x+$step_x, 'y'=>$y], ['x'=>$x+$step_x, 'y'=>$y+$step_y], ['x'=>$x, 'y'=>$y+$step_y]];
                $mid = [
                    ['x' => $interpolate($p[0]['x'], $p[1]['x'], $v[0], $v[1]), 'y' => $y],
                    ['x' => $x+$step_x, 'y' => $interpolate($p[1]['y'], $p[2]['y'], $v[1], $v[2])],
                    ['x' => $interpolate($p[3]['x'], $p[2]['x'], $v[3], $v[2]), 'y' => $y+$step_y],
                    ['x' => $x, 'y' => $interpolate($p[0]['y'], $p[3]['y'], $v[0], $v[3])]
                ];
                switch ($case) {
                    case 1: case 14: $path_data .= sprintf(' M %f %f L %f %f', $mid[3]['x'], $mid[3]['y'], $mid[0]['x'], $mid[0]['y']); break;
                    case 2: case 13: $path_data .= sprintf(' M %f %f L %f %f', $mid[0]['x'], $mid[0]['y'], $mid[1]['x'], $mid[1]['y']); break;
                    case 3: case 12: $path_data .= sprintf(' M %f %f L %f %f', $mid[3]['x'], $mid[3]['y'], $mid[1]['x'], $mid[1]['y']); break;
                    case 4: case 11: $path_data .= sprintf(' M %f %f L %f %f', $mid[1]['x'], $mid[1]['y'], $mid[2]['x'], $mid[2]['y']); break;
                    case 5:           $path_data .= sprintf(' M %f %f L %f %f M %f %f L %f %f', $mid[3]['x'], $mid[3]['y'], $mid[2]['x'], $mid[2]['y'], $mid[0]['x'], $mid[0]['y'], $mid[1]['x'], $mid[1]['y']); break;
                    case 6: case 9:  $path_data .= sprintf(' M %f %f L %f %f', $mid[0]['x'], $mid[0]['y'], $mid[2]['x'], $mid[2]['y']); break;
                    case 7: case 8:  $path_data .= sprintf(' M %f %f L %f %f', $mid[3]['x'], $mid[3]['y'], $mid[2]['x'], $mid[2]['y']); break;
                    case 10:          $path_data .= sprintf(' M %f %f L %f %f M %f %f L %f %f', $mid[3]['x'], $mid[3]['y'], $mid[0]['x'], $mid[0]['y'], $mid[1]['x'], $mid[1]['y'], $mid[2]['x'], $mid[2]['y']); break;
                }
            }
        }
        $svg .= sprintf('<path d="%s" fill="none" stroke="%s" stroke-width="%f" stroke-linecap="round"/>', $path_data, $config['plotColor'], $config['plotWidth'] / $scaleX);
    }

    // --- DRAW AXIS SCALE LABELS ---
    if ($global_config['showAxisLabels']) {
        $font_size_scaled = $global_config['axisLabelFontSize'] / $scaleY;
        $y_offset = $font_size_scaled * 1.2;
        $x_offset = $font_size_scaled * 0.5;

        // X-axis labels
        for ($x = ceil($global_config['xmin'] / $global_config['gridStepX']) * $global_config['gridStepX']; $x <= $global_config['xmax']; $x += $global_config['gridStepX']) {
            if (abs($x) < 1e-9) continue; // Skip the origin
            $svg .= sprintf(
                '<text x="%f" y="%f" transform="scale(1, -1)" fill="%s" font-size="%fpx" text-anchor="middle" dominant-baseline="hanging">%g</text>',
                $x, -$y_offset, $global_config['axisLabelColor'], $font_size_scaled, $x
            );
        }
        // Y-axis labels
        for ($y = ceil($global_config['ymin'] / $global_config['gridStepY']) * $global_config['gridStepY']; $y <= $global_config['ymax']; $y += $global_config['gridStepY']) {
            if (abs($y) < 1e-9) continue; // Skip the origin
            $svg .= sprintf(
                '<text x="%f" y="%f" transform="scale(1, -1)" fill="%s" font-size="%fpx" text-anchor="end" dominant-baseline="middle">%g</text>',
                -$x_offset, -$y, $global_config['axisLabelColor'], $font_size_scaled, $y
            );
        }
    }

    // --- DRAW CUSTOM LABELS ---
    foreach ($global_config['labels'] as $label) {
        $x = $label['x'] ?? 0;
        $y = $label['y'] ?? 0;
        $text = $label['text'] ?? '';
        $color = $label['color'] ?? '#000000';
        $fontSize = $label['fontSize'] ?? 12;
        $anchor = $label['anchor'] ?? 'middle'; // middle, start, end

        $font_size_scaled = $fontSize / $scaleY;
        
        $svg .= sprintf(
            '<text x="%f" y="%f" transform="scale(1, -1)" fill="%s" font-size="%fpx" text-anchor="%s" dominant-baseline="middle">%s</text>',
            $x, -$y, $color, $font_size_scaled, $anchor, htmlspecialchars($text)
        );
    }


    // --- FINISH SVG ---
    $svg .= '</g></svg>';
    return $svg;
}