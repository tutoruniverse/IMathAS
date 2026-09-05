<?php
require '../init_without_validate.php';

$pagetitle = "Answers with Units";
$ispublic = true;
require '../header.php';

?>
<style>
	ul {
		margin-bottom: 1ex;
	}
</style>
<h1>How to Enter an Answer with Units</h1>
<p>An answer that requires units should have a decimal or integer number followed by units, such as
	<em><span style="color:blue">5.6 feet</span></em>, or <em><span style="color:blue">3 seconds</span></em>.
</p>
<p>In your answer, you can multiply units together, raise them to powers, or have a fraction of units.
	Examples include <em><span style="color:blue">144 m^2</span></em> or
	<em><span style="color:blue">75 meters/second</span></em> or
	<em><span style="color:blue">8 kg*m/s^2</span></em>.
</p>
<p>The system understands that <em><span style="color:blue">6 feet per second<span></em> means
	<em><span style="color:blue">6 ft/s</span></em>. It also understands that
	<em><span style="color:blue">7 meters squared</span></em> means
	<em><span style="color:blue">7 m^2</span></em>, and that
	<em><span style="color:blue">8 meters cubed</span></em> means
	<em><span style="color:blue">8 m^3</span></em>.
</p>
<p>If the system does not recognize a unit you have used, it will display an error message.
</p>
<p>The system can recognize many different units of measurement. Listed below are all of
	the units of each type that the system will recognize. Note that some units can be abbreviated,
	such as using <em><span style="color:blue">m</span></em> for
	<em><span style="color:blue">meter</span></em> or
	<em><span style="color:blue">ft</span></em> for
	<em><span style="color:blue">feet</span></em>.
</p>
<h2>Units Available</h2>
<ul><li>Metric Prefixes
	<ul>
		<li>You can use metric prefixes on metric units, such as <em><span style="color:blue">millimeters</span></em>. Use abbreviated metric prefixes on abbreviated metric units, such as <em><span style="color:blue">mL</span></em>.</li>
		<li>yotta,Y</li>
		<li>zetta,Z</li>
		<li>exa,E</li>
		<li>peta,P</li>
		<li>tera,T</li>
		<li>giga,G</li>
		<li>mega,M</li>
		<li>kilo,k</li>
		<li>hecto,h</li>
		<li>deka,da</li>
		<li>deci,d</li>
		<li>centi,c</li>
		<li>milli,m</li>
		<li>micro,u</li>
		<li>nano,n</li>
		<li>pico,p</li>
		<li>fempto,f</li>
		<li>atto,a</li>
		<li>zepto,z</li>
		<li>yocto,y</li>
	</ul>

</li><li>Length
	<ul>
		<li>meter,m</li>
		<li>inch,in</li>
		<li>foot,ft</li>
		<li>yard,yd</li>
		<li>mile,mi</li>
		<li>micron</li>
		<li>angstrom</li>
		<li>fermi</li>
		<li>furlong</li>
	</ul>

</li><li>Area
	<ul>
		<li>acre</li>
		<li>hectare,ha</li>
		<li>barn,b</li>
		<li>Any length unit squared (such as <em><span style="color:blue">m^2</span></em> or <em><span style="color:blue">foot squared</span></em>)</li>
	</ul>

</li><li>Volume
	<ul>
		<li>liter,L</li>
		<li>cc (<em>cubic centimeters</em>)</li>
		<li>gallon,gal</li>
		<li>cup</li>
		<li>pint,pt</li>
		<li>quart,qt</li>
		<li>tablespoon,tbsp</li>
		<li>teaspoon,tsp</li>
		<li>Any length unit cubed (such as <em><span style="color:blue">m^3</span></em> or <em><span style="color:blue">foot cubed</span></em>)</li>
	</ul>

</li><li>Time
	<ul>
		<li>second,sec,s</li>
		<li>minute,min</li>
		<li>hour,hr</li>
		<li>day</li>
		<li>week</li>
		<li>month,mo</li>
		<li>year,yr</li>
		<li>fortnight</li>
	</ul>

</li><li>Velocity
	<ul>
		<li>mph</li>
		<li>kph</li>
		<li>knot,kt</li>
		<li>c (<em>the speed of light</em>)</li>
		<li>Any length unit divided by a time unit (such as <em><span style="color:blue">m/s</span></em> or <em><span style="color:blue">inches per minute</span></em>)</li>
	</ul>

</li><li>Mass
	<ul>
		<li>gram,g</li>
		<li>tonne,t (<em>metric ton</em>)</li>
	</ul>

</li><li>Force
	<ul>
		<li>newton,N</li>
		<li>kip</li>
		<li>dyne,dyn</li>
		<li>pound,lb,lbf (<em>pound force</em>)</li>
		<li>ton</li>
	</ul>

</li><li>Energy
	<ul>
		<li>joule,J</li>
		<li>erg</li>
		<li>ftlb,lbft</li>
		<li>calorie,cal</li>
		<li>electronvolt,eV</li>
		<li>Wh (<em>watt-hour</em>)</li>
		<li>Btu</li>
		<li>therm</li>
	</ul>

</li><li>Power
	<ul>
		<li>watt,W</li>
		<li>horsepower,hp</li>
	</ul>

</li><li>Pressure
	<ul>
		<li>pascal,Pa</li>
		<li>atmosphere,atm</li>
		<li>bar</li>
		<li>Torr</li>
		<li>mmHg</li>
		<li>umHg</li>
		<li>cmWater</li>
		<li>psi</li>
		<li>ksi</li>
		<li>Mpsi</li>
	</ul>

</li><li>Angles
	<ul>
		<li>radian,rad</li>
		<li>degree,deg</li>
		<li>arcminute</li>
		<li>arcsecond</li>
		<li>gradian,grad</li>
	</ul>

</li><li>Frequency
	<ul>
		<li>hertz,Hz</li>
		<li>revolution,rev</li>
		<li>cycle</li>
	</ul>

</li><li>Electrical Units
	<ul>
		<li>coulomb,C</li>
		<li>volt,V</li>
		<li>farad,F</li>
		<li>ohm</li>
		<li>amp,ampere,A</li>
	</ul>

</li><li>Magnetic Units
	<ul>
		<li>tesla,T</li>
		<li>gauss,G</li>
		<li>weber,Wb</li>
		<li>henry,H</li>
	</ul>

</li><li>Luminosity
	<ul>
		<li>lumen,lm</li>
		<li>lux,lx</li>
	</ul>

</li><li>Atomic Units
	<ul>
		<li>amu (<em>atomic mass unit</em>)</li>
		<li>dalton,Da</li>
		<li>me (<em>electron mass</em>)</li>
	</ul>

</li><li>Other Science Units
	<ul>
		<li>mole,mol</li>
		<li>M (<em>molar</em>)</li>
		<li>curie,Ci</li>
		<li>roentgen,R</li>
		<li>steradian,sr</li>
		<li>becquerel,Bq</li>
	</ul>

</li><li>Astronomy Units
	<ul>
		<li>lightsecond,ls</li>
		<li>lightyear,ly</li>
		<li>AU (<em>astronomical unit</em>)</li>
		<li>parsec,pc</li>
		<li>solarmass</li>
		<li>solarradius</li>
	</ul>

</li><li>Temperature
	<ul>
		<li>degF</li>
		<li>degC</li>
		<li>degK,K</li>
	</ul>
</li>
</ul>

<?php

require '../footer.php';
