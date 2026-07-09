<html>
<head>
<title>ASCIISvg Reference</title>
<link rel="stylesheet" href="../assessment/libs/help.css?v=112325" type="text/css" />
</head>
<body>
<h1>ASCIIsvg Reference</h1>
<p>ASCIIsvg is a programatic drawing language, allowing you to create randomized basic drawings and illustrations.</p>
<p>When using the <strong>showasciisvg</strong> macro when writing a question the format is</p>
<pre>
$pic = showasciisvg(asciisvgcode,[width,height,alttext])
</pre>
<p>Here asciisvgcode is a string, enclosed in quotes, using the following commands, separated with semicolons.&nbsp; Beware that since this string is enclosed in quotes, any quotes needed in the string either need to be 'single quotes' or escaped using backlashes like \"this\"</p>
<details class="example"><summary>Examples</summary>
<pre style="white-space: pre-wrap">$pic = showasciisvg("setBorder(20);initPicture(-5,5,0,30); axes(1,5,1); plot('x^2'); marker='arrow'; line([1,1],[4,4]);")</pre>
</details>
<p>Additional examples can be found in the "Examples" question library.</p>

<h2>Command Reference</h2>
<p>optional parameters are shown in {curly brackets}. These can be left out if not needed.</p>
<p><strong>setBorder(border)</strong> or <strong>setBorder(left,bottom,right,top)</strong>:&nbsp; Sets the border, in pixels, around the main drawing window.&nbsp; Text can spill into the border, which is why one is often used.&nbsp; This should be used before calling initPicture.</p>
<details class="example"><summary>Examples</summary>
<pre>// set a larger left and bottom border
setBorder(30,30,5,5);initPicture(0,5,0,5);
</pre></details>

<p><strong>initPicture(xmin,xmax,{ymin,ymax})</strong>:&nbsp; Initializes the drawing window.</p>
<details class="example"><summary>Examples</summary>
<pre>initPicture(-5,5,0,10); // -5 to 5 in x, 0 to 10 in y
initPicture(-5,5); // -5 to 5 in both x and y
</pre></details>

<p><strong>axes(xtick,ytick,{labels,xgrid,ygrid,dox,doy})</strong>:&nbsp; Draws axes.&nbsp; xtick and ytick are numbers giving the spacing of tick marks and labels on the x and y axes respectively.&nbsp; Set <em>labels</em> to 0 to turn off axes labels, 1 to turn them on.&nbsp; xgrid and ygrid can set the spacing of x and y grid lines; set to 0 to turn off.&nbsp; dox and doy can be set to 0 to turn off tick marks and labels for the corresponding axis.</p>
<details class="example"><summary>Examples</summary>
<pre>axes(1,1,1,1,1); // your standard axes with grids and labels by 1 
axes(1,2); // basic axes with ticks spaced at 1 in x 2 in y, no grid or labels.
axes(1,2,1); // axes with labels at 1 in x 2 in y, no grid
axes(1,2,1,2,4); // as above, but with grid at 2 in x 4 in y
axes(1,1,1,1,1,1,0); // doy off, no y axis or vertical labels/grid
axes(0,1,1); // xtick 0 will show no tick marks or labels on x-axis bu
</pre></details>

<p><strong>plot("f(x)",{xmin,xmax,steps})</strong>:&nbsp; Plot function f(x).&nbsp;&nbsp; Can specify limited domain and number of steps if desired.</p>
<details class="example"><summary>Examples</summary>
<pre>plot('x^2'); // plots x^2
plot('x',0,2); // plots y=x from x=0 to 2
</pre></details>

<p><strong>plot(["x(t)","y(t)"],{tmin,tmax,steps})</strong>:&nbsp; Plot parametric curve</p>
<details class="example"><summary>Examples</summary>
<pre>plot(['sin(t)','cos(t)'],0,2*pi); // parametric circle
</pre></details>

<p><strong>slopefield("dy/dx",{xstep,ystep})</strong>:&nbsp; Plot a slopefield/direction field.&nbsp; <em>xstep</em> and <em>ystep</em> are spacing of slopes.</p>
<details class="example"><summary>Examples</summary>
<pre>slopefield('x',1,2); // dy/dx=x, spaced 1 in x 2 in y
</pre></details>

<p><strong>line([x1,y1],[x2,y2])</strong>:&nbsp; draw line from point [x1,y1] to [x2,y2]</p>
<details class="example"><summary>Examples</summary>
<pre>line([1,1],[2,3]); // line from (1,1) to (2,3)
</pre></details>

<p><strong>path([[x1,y1],...,[xn,yn]])</strong>:&nbsp; draws line segments connecting the list of points</p>
<details class="example"><summary>Examples</summary>
<pre>path([[1,1],[2,3],[3,0],[4,0]]); // connect the dots
</pre></details>

<p><strong>circle([x1,y1],rad)</strong>:&nbsp; draws a circle centered at [x1,y1] with radius <em>rad</em></p>
<details class="example"><summary>Examples</summary>
<pre>circle([1,1], 4);
</pre></details>

<p><strong>ellipse([x1,y1],xrad,yrad)</strong>:&nbsp; draws an ellipse with given center and radius in the x and y directions</p>
<details class="example"><summary>Examples</summary>
<pre>ellipse([1,1], 3, 2);
</pre></details>

<p><strong>arc([x1,y1],[x2,y2],rad)</strong>:&nbsp; draws a circular arc counter-clockwise from [x1,y1] to [x2,y2] with radius <em>rad</em></p>
<details class="example"><summary>Examples</summary>
<pre>arc([1,1],[2,3]);
arc([2,3],[1,1]); // arc curves other way 
</pre></details>

<p><strong>sector([x,y],radius,angle_start,angle_end)</strong>: Draws a sector with center of circle at x,y<strong></p>
<details class="example"><summary>Examples</summary>
<pre>sector([1,1],5,0,pi/4); 
</pre></details>

<p>rect([x1,y1],[x2,y2])</strong>:&nbsp; draws a rectangle with given diagonally opposite corner points</p>
<details class="example"><summary>Examples</summary>
<pre>rect([1,1],[3,7]); 
</pre></details>

<p><strong>dot([x1,y1],{type,label,pos})</strong>:&nbsp;&nbsp; draws a dot at the given point.&nbsp; <em>type</em> can be used to set the type: open or closed (default).&nbsp; Optionally, a label for the dot can be provided, and the position for that label can be specified (default below).</p>
<details class="example"><summary>Examples</summary>
<pre>dot([1,1]); // basic closed dot 
dot([1,1],'open'); // open dot
dot([1,1],'closed','A'); // closed dot label A below 
dot([1,1],'closed','A','aboveright'); // label aboveright
</pre></details>

<p><strong>text([x1,y1],"string",{pos,angle})</strong>: &nbsp; draws the basic text string at given point.&nbsp; No html or typeset math.&nbsp; By default, center of text is placed at given point;&nbsp; <em>pos</em> can be used to change. pos should be a string like left,right,above,below,aboveleft,etc.&nbsp; <em>angle</em>, in degrees, can be used to rotate the text.</p>
<details class="example"><summary>Examples</summary>
<pre>text([1,1],'A'); // A centered at 1,1
text([1,1],'A','left'); // A positioned left of 1,1
text([1,1],'A','',90); // A rotated 90deg, centered at 1,1
text([1,1],'`x^2`'); // a math label
</pre></details>

<p><strong>textabs([pixelx,pixely],"string",{pos,angle})</strong>:&nbsp; same as text function, but coordinates are pixel locations rather than coordinate system locations.</p>
<details class="example"><summary>Examples</summary>
<pre>// for this, assume we set the width and height to 300
// if want a label along the left edge of the window
// rotated sideways
textabs([0,150],'Side Label','right',90);
</pre></details>

<h3>Settings</h3>
<p><strong>stroke = "color"</strong>:&nbsp; sets line color:&nbsp; white, black, gray, red, orange, yellow, green, blue, cyan, purple</p>
<details class="example"><summary>Examples</summary>
<pre>stroke='blue'; line([1,1],[2,2]); // line will be in blue
</pre></details>

<p><strong>fill = "color"</strong>:&nbsp; sets the fill for rectangles, circles, etc. &nbsp;Same colors as above plus transred, transblue, transgreen for translucent colors.</p>
<details class="example"><summary>Examples</summary>
<pre>fill='blue'; stroke='red'; rect([1,1],[2,2]); // rectangle will have red outline, blue fill
// could use stroke='none'; for no outline
</pre></details>

<p><strong>fontfill = "color"</strong> :&nbsp; sets the text color</p>
<details class="example"><summary>Examples</summary>
<pre>fontfill='blue'; text([1,1],'A'); // text will be blue
</pre></details>

<p><strong>fontbackground = "color"</strong> : background color for text</p>
<details class="example"><summary>Examples</summary>
<pre>fontbackground='white'; text([1,1],'A'); // text will have a white background 
// handy when the text may be on top of a line or something
</pre></details>

<p><strong>strokewidth=width</strong>:&nbsp; sets line thickness</p>
<details class="example"><summary>Examples</summary>
<pre>strokewidth=3; path([[1,2],[3,4],[5,6]]); // thicker path
</pre></details>

<p><strong>strokedasharray="array"</strong>:&nbsp; dash array, ie "5 3" for 5 pixels color, 3 pixels white</p>
<details class="example"><summary>Examples</summary>
<pre>strokedasharray="5 5"; plot('x^2'); // a dashed parabola
</pre></details>

<p><strong>marker = "marker" </strong>:&nbsp; turns on marks for the end of line segments.&nbsp; marker: "dot", "arrow", "arrowdot" or "none"</p>
<details class="example"><summary>Examples</summary>
<pre>marker='dot'; path([[1,2],[3,4],[5,6]]); // a dot at each point on the path
marker='arrow'; line([1,1],[2,2]); // line with arrow at 2,2
</pre></details>

</body>
</head>