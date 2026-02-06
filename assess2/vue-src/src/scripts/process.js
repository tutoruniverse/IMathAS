/* eslint-disable no-cond-assign */
/* eslint-disable no-control-regex */
/* eslint-disable no-undef */
/* eslint-disable no-redeclare */
/* eslint-disable no-useless-escape */
var jQuery;
var imathasDraw;
var commasep;
var unitsregexmatch;

var _ = (str) => str;
//
var initstack = [];
var loadedscripts = [];
var scriptqueue = [];
var processingscriptsqueue = false;
var callbackstack = {};
//
var allParams = {};
var allKekule = {};

function processScriptQueue() {
  processingscriptsqueue = true;
  if (scriptqueue.length == 0) { return; }
  var nextscript = scriptqueue.shift();

  if (nextscript[0] == 'code') {
    try {
      window.eval(nextscript[1]);
    } catch (e) { console.log("Error executing question script:" + nextscript[1]); }
    processScriptQueueNext();
  } else {
    jQuery.getScript(nextscript[1]).always(function () { // force sync
      processScriptQueueNext();
    });
  }
}
function processScriptQueueNext() {
  for (var i = 0; i < initstack.length; i++) {
    var foo = initstack[i]();
  }
  initstack.length = 0;
  if (scriptqueue.length == 0) {
    processingscriptsqueue = false;
  } else {
    processScriptQueue();
  }
}
/**
 * Processes each question type.  Return object has:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */
function processByType(qn) {
  if (!allParams.hasOwnProperty(qn)) {
    return false;
  }
  var params = allParams[qn];
  var res = {};
  if (params.qtype == 'draw') {
    imathasDraw.encodea11ydraw();
    return {};
  } else if (params.qtype == 'choices' || params.qtype == 'multans' || params.qtype == 'matching') {
    return {};
  } else if (params.hasOwnProperty('matrixsize')) {
    res = processSizedMatrix(qn);
  } else if (params.qtype == 'molecule') {
    processMolecule(qn);
    return {};
  } else {
    var el = document.getElementById('qn' + qn);
    if (!el) {
      return false;
    }
    var str = el.value;

    str = normalizemathunicode(str);
    str = str.replace(/^\s+/, '').replace(/\s+$/, '');
    if (str.match(/^\s*$/)) {
      return { str: '', displvalstr: '', submitstr: '' };
    } else if (str.match(/^\s*DNE\s*$/i)) {
      return { str: 'DNE', displvalstr: '', submitstr: 'DNE' };
    } else if (str.match(/^\s*oo\s*$/i)) {
      return { str: 'oo', displvalstr: '', submitstr: 'oo' };
    } else if (str.match(/^\s*\+oo\s*$/i)) {
      return { str: '+oo', displvalstr: '', submitstr: '+oo' };
    } else if (str.match(/^\s*-oo\s*$/i)) {
      return { str: '-oo', displvalstr: '', submitstr: '-oo' };
    }
    switch (params.qtype) {
      case 'number':
        res = processNumber(str, params.calcformat);
        break;
      case 'calculated':
        res = processCalculated(str, params.calcformat);
        break;
      case 'interval':
      case 'calcinterval':
        res = processCalcInterval(str, params.calcformat, params.vars);
        break;
      case 'ntuple':
      case 'calcntuple':
      case 'complexntuple':
      case 'calccomplexntuple':
      case 'algntuple':
        res = processCalcNtuple(qn, str, params.calcformat, params.qtype);
        break;
      case 'complex':
      case 'calccomplex':
        res = processCalcComplex(str, params.calcformat);
        break;
      case 'numfunc':
        res = processNumfunc(qn, str, params.calcformat);
        break;
      case 'matrix':
      case 'complexmatrix':
        res = processCalcMatrix(qn, str, params.calcformat, params.qtype);
        break;
      case 'calcmatrix':
      case 'calccomplexmatrix':
      case 'algmatrix':
        res = processCalcMatrix(qn, str, params.calcformat, params.qtype);
        break;
    }
    res.str = preformat(qn, str, params.qtype, params.calcformat);
  }
  return res;
}

/**
 *  These functions should return:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */

function processNumber(origstr, format) {
  var err = '';
  origstr = origstr.replace(/^\s+|\s+$/g, '');
  if (format.indexOf('set') !== -1) {
    if (origstr.charAt(0) !== '{' || origstr.substr(-1) !== '}') {
      err += _('Invalid set notation');
    } else {
      origstr = origstr.slice(1, -1);
    }
  }
  if (format.indexOf('list') !== -1 || format.indexOf('set') !== -1) {
    var strs = origstr.split(/\s*,\s*/);
  } else {
    if (!commasep && origstr.match(/,/)) {
      err += _("Invalid use of a comma.");
    }
    var strs = [origstr.replace(/,/g, '')];
  }
  var str;
  for (var j = 0; j < strs.length; j++) {
    str = strs[j];
    if (format.indexOf('units') != -1) {
      var unitformat = _('Units must be given as [decimal number]*[unit]^[power]*[unit]^[power].../[unit]^[power]*[unit]^[power]...');
      if (!str.match(/^\s*(-?\s*\d+\.?\d*|-?\s*\.\d+|-?\s*\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+)/)) {
        err += _('Answer must start with a number. ');
      }
      // disallow (sq|cu|square|cubic|squared|cubed)^power
      if (str.match(/\b(sq|square|cu|cubic|squared|cubed)\s*\^\s*[\-\+]?\s*\d+/)) {
        err += _('Invalid base for exponent. ');
        str = str.replace(/\^/, '');
      }
      // disallow (sq|square|cu|cubic)per
      if (str.match(/\b(sq|square|cu|cubic)\s+per\b/)) {
        err += _('Missing unit before "per". ');
        str = str.replace(/per\b/, '');
      }
      // disallow per(squared|cubed)
      if (str.match(/\bper\s+(squared|cubed)/)) {
        err += _('Missing unit after "per". ');
        str = str.replace(/\bper/, '');
      }
      // strip unit^number (squared|cubed)
      str = str.replace(/([a-zA-Z]\w*\s*)(\^\s*[\-\+]?\s*\d+\s*)(?:squared|cubed)\b/g, '$1');
      // strip (sq|cu|square|cubic) unit and unit (squared|cubed) since those are valid
      str = str.replace(/(?:sq|square|cu|cubic)\s+([a-zA-Z]\w*)/g, '$1');
      str = str.replace(/([a-zA-Z]\w*)\s+(?:squared|cubed)/g, '$1');
      // "this per that" => this/that
      str = str.replace(/\sper\s/g, '/');
      // strip number
      str = str.replace(/^\s*(-?\s*\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+|-?\s*\d+\.?\d*|-?\s*\.\d+)\s*[\-\*]?\s*/, '');
      str = str.replace(/\s*\-\s*([a-zA-Z])/g, '*$1');
      str = str.replace(/\*\*/g, '^');
      str = str.replace(/\s*(\/|\^|\-)\s*/g, '$1');
      str = str.replace(/\(\s*(.*?)\s*\)\s*\//, '$1/').replace(/\/\s*\(\s*(.*?)\s*\)/, '/$1');
      str = str.replace(/\s*[\*\s]\s*/g, '*');
      // strip word^power since those are valid
      str = str.replace(/([a-zA-Z]\w*)\^[\-\+]?\d+/g, '$1');
      if (str.match(/\^/)) {
        err += _('Invalid exponents. ');
        str = str.replace(/\^/g, '');
      }
      if ((str.match(/\//g) || []).length > 1) {
        err += _('Only one division symbol allowed in the units. ');
      }

      str = str.replace(/\//g, '*').replace(/^\s*\*/, '').trim();

      if (str.length > 0) {
        var unitsregexlongprefix = "(yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deca|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto)";
        var unitsregexabbprefix = "(Y|Z|E|P|T|G|M|k|h|da|d|c|m|u|n|p|f|a|z|y)";
        var unitsregexfull = /^(m|meter|metre|micron|angstrom|fermi|in|inch|inches|ft|foot|feet|mi|mile|furlong|yd|yard|s|sec|second|min|minute|h|hr|hour|day|week|mo|month|yr|year|fortnight|acre|ha|hectare|b|barn|L|liter|litre|cc|gal|gallon|cup|pt|pint|qt|quart|tbsp|tablespoon|tsp|teaspoon|rad|radian|deg|degree|arcminute|arcsecond|grad|gradian|knot|kt|c|mph|kph|g|gram|gramme|t|tonne|Hz|hertz|rev|revolution|cycle|N|newton|kip|dyn|dyne|lb|pound|lbf|ton|J|joule|erg|lbft|ftlb|cal|calorie|eV|electronvolt|Wh|Btu|therm|W|watt|hp|horsepower|Pa|pascal|atm|atmosphere|bar|Torr|mmHg|umHg|cmWater|psi|ksi|Mpsi|C|coulomb|V|volt|farad|F|ohm|amp|ampere|A|T|tesla|G|gauss|Wb|weber|H|henry|lm|lumen|lx|lux|amu|dalton|Da|me|mol|mole|Ci|curie|R|roentgen|sr|steradian|Bq|becquerel|ls|lightsecond|ly|lightyear|AU|au|parsec|pc|solarmass|solarradius|degF|degC|degK|K)$/;
        //000 noprefix, noplural, sensitive
        //var unitsregex000 = "(in|mi|yd|min|h|hr|mo|yr|ha|gal|pt|qt|tbsp|tsp|rad|deg|grad|kt|c|mph|kph|rev|lbf|atm|mmHg|umHg|cmWater|psi|ksi|Mpsi|weber|amu|me|R|AU|au|degF|degC|degK|K)";
        //100 abb, noplural, sensitive
        var unitsregex100 = "(m|ft|s|b|cc|g|t|N|dyn|J|cal|eV|Wh|W|hp|Pa|C|V|F|A|T|G|Wb|H|lm|lx|Da|mol|M|Ci|sr|Bq|ls|ly|pc)";
        //001 noprefix, noplural, insensitive
        var unitsregex001 = "(inch|inches|lbft|ftlb|solarmass|solarradius)";
        //101 abb, noplural, insensitive
        var unitsregex101 = "(L|Hz|Btu)";
        //201 long, noplural, insensitive
        var unitsregex201 = "(fermi|foot|feet|sec|hertz|horsepower|Torr|gauss|lux)";
        //011 noprefix, plural, insensitive
        var unitsregex011 = "(micron|mile|furlong|yard|minute|hour|day|week|month|year|fortnight|acre|hectare|gallon|cup|pint|quart|tablespoon|teaspoon|radian|degree|gradian|knot|revolution|cycle|kip|lb|therm|atmosphere|roentgen)";
        //211 long, plural, insensitive
        var unitsregex211 = "(meter|metre|angstrom|second|barn|liter|litre|arcminute|arcsecond|gram|gramme|tonne|newton|dyne|pound|ton|joule|erg|calorie|electronvolt|watt|pascal|coulomb|volt|farad|ohm|amp|ampere|tesla|weber|henry|lumen|dalton|mole|curie|steradian|becquerel|lightsecond|lightyear|parsec)";

        var unitsregexfull100 = new RegExp("^" + unitsregexabbprefix + "?" + unitsregex100 + "$");
        var unitsregexfull001 = new RegExp("^" + unitsregex001 + "$", 'i');
        var unitsregexfull101 = new RegExp("^" + unitsregexabbprefix + "?" + unitsregex101 + "$", 'i');
        var unitsregexfull201 = new RegExp("^" + unitsregexlongprefix + "?" + unitsregex201 + "$", 'i');
        var unitsregexfull011 = new RegExp("^" + unitsregex011 + "s?$", 'i');
        var unitsregexfull211 = new RegExp("^" + unitsregexlongprefix + "?" + unitsregex211 + "s?$", 'i');

        var pts = str.split(/\s*\*\s*/);
        for (var i = 0; i < pts.length; i++) {
          // get matches
          unitsregexmatch = pts[i].match(unitsregexfull101);
          let unitsbadcase = false;
          // It should have three defined matches:  [fullmatch, prefix, unit]
          if (unitsregexmatch && typeof unitsregexmatch[1] !== 'undefined') {
            // check that the prefix match is case sensitive
            var unitsregexabbprefixfull = new RegExp("^" + unitsregexabbprefix + "$");
            if (!unitsregexabbprefixfull.test(unitsregexmatch[1])) {
              unitsbadcase = true;
            }
          }
          if ((!unitsregexfull.test(pts[i]) && !unitsregexfull100.test(pts[i]) && !unitsregexfull001.test(pts[i]) && !unitsregexfull101.test(pts[i]) && !unitsregexfull201.test(pts[i]) && !unitsregexfull011.test(pts[i]) && !unitsregexfull211.test(pts[i])) || unitsbadcase) {
            err += _('Unknown unit ') + '"' + pts[i] + '". ';
          }
        }
      } else {
        err += _("Missing units");
      }
    } else if (format.indexOf('integer') != -1) {
      if (!str.match(/^\s*\-?\d+\s*$/)) {
        err += _('This is not an integer.');
      }
    } else {
      if (!str.match(/^\s*(\+|\-)?(\d+\.?\d*|\.\d+|\d*\.?\d*\s*E\s*[\-\+]?\d+)\s*$/)) {
        err += _('This is not a decimal or integer value.');
      }
    }
  }
  return {
    err: err,
    dispvalstr: origstr
  };
}
function processCalculated(fullstr, format) {
  // give error instead.  fullstr = fullstr.replace(/=/,'');
  if (format.indexOf('allowplusminus') != -1) {
    fullstr = fullstr.replace(/(.*?)\+\-(.*?)(,|$)/g, '$1+$2,$1-$2$3');
  }
  if (format.indexOf('list') != -1) {
    var strarr = fullstr.split(/,/);
  } else if (format.indexOf('set') != -1) {
    var strarr = fullstr.replace(/[\{\}]/g, '').split(/,/);
  } else {
    var strarr = [fullstr];
  }
  var err = '', res, outvals = [];
  for (var sc = 0; sc < strarr.length; sc++) {
    str = strarr[sc];
    err += singlevalsyntaxcheck(str, format);
    err += syntaxcheckexpr(str, format);
    res = singlevaleval(str, format);
    err += res[1];
    outvals.push(res[0]);
  }
  var dispstr = roundForDisp(outvals).join(', ');
  if (format.indexOf('set') != -1) {
    dispstr = '{' + dispstr + '}';
  }
  return {
    err: err,
    dispvalstr: dispstr,
    submitstr: outvals.join(',')
  };
}


function processCalcInterval(fullstr, format, ineqvar) {
  var origstr = fullstr;
  fullstr = fullstr.replace(/cup/g, 'U');
  if (format.indexOf('inequality') != -1) {
    fullstr = fullstr.replace(/or/g, ' or ');
    var conv = ineqtointerval(fullstr, ineqvar);
    if (conv.length > 1) { // has error
      return {
        err: (conv[1] == 'wrongvar') ?
          _('you may have used the wrong variable') :
          _('invalid inequality notation')
      };
    }
    fullstr = conv[0];
  }
  var strarr = [], submitstrarr = [], dispstrarr = [], joinchar = 'U';
  //split into array of intervals
  if (format.indexOf('list') != -1) {
    fullstr = fullstr.replace(/\s*,\s*/g, ',').replace(/(^,|,$)/g, '');
    joinchar = ',';
    var lastpos = 0;
    for (var pos = 1; pos < fullstr.length - 1; pos++) {
      if (fullstr.charAt(pos) == ',') {
        if ((fullstr.charAt(pos - 1) == ')' || fullstr.charAt(pos - 1) == ']')
          && (fullstr.charAt(pos + 1) == '(' || fullstr.charAt(pos + 1) == '[')
        ) {
          strarr.push(fullstr.substring(lastpos, pos));
          lastpos = pos + 1;
        }
      }
    }
    strarr.push(fullstr.substring(lastpos));
  } else {
    strarr = fullstr.split(/\s*U\s*/i);
  }

  var err = ''; var str, vals, res, calcvals = [], calcvalsdisp = [];
  for (i = 0; i < strarr.length; i++) {
    str = strarr[i];
    sm = str.charAt(0);
    em = str.charAt(str.length - 1);
    vals = str.substring(1, str.length - 1);
    vals = vals.split(/,/);
    // check right basic format
    if (vals.length != 2 || ((sm != '(' && sm != '[') || (em != ')' && em != ']'))) {
      if (format.indexOf('inequality') != -1) {
        err += _("invalid inequality notation") + '. ';
      } else {
        err += _("invalid interval notation") + '. ';
      }
      break;
    }
    for (j = 0; j < 2; j++) {
      if (format.indexOf('decimal') != -1 && vals[j].match(/[\d\.]e\-?\d/)) {
        vals[j] = vals[j].replace(/e/, "E"); // allow 3e-4 in place of 3E-4 for decimal answers
      }
      err += singlevalsyntaxcheck(vals[j], format);
      err += syntaxcheckexpr(vals[j], format);
      if (vals[j].match(/^\s*\-?\+?oo\s*$/)) {
        calcvals[j] = vals[j];
      } else {
        res = singlevaleval(vals[j], format);
        err += res[1];
        calcvals[j] = res[0];
      }
    }
    calcvalsdisp = roundForDisp(calcvals);
    submitstrarr[i] = sm + calcvals[0] + ',' + calcvals[1] + em;
    if (format.indexOf('inequality') != -1) {
      // reformat as inequality
      if (calcvals[0].toString().match(/oo/)) {
        if (calcvals[1].toString().match(/oo/)) {
          dispstrarr[i] = 'RR';
        } else {
          dispstrarr[i] = ineqvar + (em == ']' ? ' le ' : ' lt ') + calcvalsdisp[1];
        }
      } else if (calcvals[1].toString().match(/oo/)) {
        dispstrarr[i] = ineqvar + (sm == '[' ? ' ge ' : ' gt ') + calcvalsdisp[0];
      } else {
        dispstrarr[i] = calcvalsdisp[0] + (sm == '[' ? ' le ' : ' lt ') + ineqvar + (em == ']' ? ' le ' : ' lt ') + calcvalsdisp[1];
      }
    } else {
      dispstrarr[i] = sm + calcvalsdisp[0] + ',' + calcvalsdisp[1] + em;
    }
  }
  if (format.indexOf('inequality') != -1) {
    return {
      err: err,
      dispvalstr: dispstrarr.join(' "or" '),
      submitstr: submitstrarr.join(joinchar)
    };
  } else {
    return {
      err: err,
      dispvalstr: dispstrarr.join(' uu '),
      submitstr: submitstrarr.join(joinchar)
    };
  }
}

function processCalcNtuple(qn, fullstr, format, qtype) {
  var outcalced = '';
  var outcalceddisp = '';
  var NCdepth = 0;
  var lastcut = 0;
  var err = "";
  var notationok = true;
  var res = NaN;
  var dec;
  // Need to be able to handle (2,3),(4,5) and (2(2),3),(4,5) while avoiding (2)(3,4)
  fullstr = normalizemathunicode(fullstr);
  fullstr = fullstr.replace(/(\s+,\s+|,\s+|\s+,)/g, ',').replace(/(^,|,$)/g, '');
  fullstr = fullstr.replace(/<<(.*?)>>/g, '<$1>');
  if (!fullstr.charAt(0).match(/[\(\[\<\{]/)) {
    notationok = false;
  }
  for (var i = 0; i < fullstr.length; i++) {
    dec = false;
    if (NCdepth == 0) {
      outcalced += fullstr.charAt(i);
      outcalceddisp += fullstr.charAt(i);
      lastcut = i + 1;
      if (fullstr.charAt(i) == ',') {
        if (!fullstr.substring(i + 1).match(/^\s*[\(\[\<\{]/) ||
          !fullstr.substring(0, i).match(/[\)\]\>\}]\s*$/)
        ) {
          notationok = false;
        }
      } else if (i > 0 && fullstr.charAt(i - 1) != ',') {
        notationok = false;
      }
    }
    if (fullstr.charAt(i).match(/[\(\[\<\{]/)) {
      NCdepth++;
    } else if (fullstr.charAt(i).match(/[\)\]\>\}]/)) {
      NCdepth--;
      dec = true;
    }

    if ((NCdepth == 0 && dec) || (NCdepth == 1 && fullstr.charAt(i) == ',')) {
      sub = fullstr.substring(lastcut, i).replace(/^\s+/, '').replace(/\s+$/, '');
      if (sub == '') { notationok = false; }
      if (qtype.match(/complex/)) {
        res = evalcheckcomplex(sub, format);
        err += res.err;
        outcalceddisp += res.outstrdisp;
        outcalceddisp += fullstr.charAt(i);
        if (res.outstr) {
          outcalced += res.outstr;
          outcalced += fullstr.charAt(i);
        }
      } else if (qtype === 'algntuple') {
        res = processNumfunc(qn, sub, format);
        err += res.err;
      } else {
        err += singlevalsyntaxcheck(sub, format);
        err += syntaxcheckexpr(sub, format);
        res = singlevaleval(sub, format);
        err += res[1];
        outcalced += res[0];
        outcalceddisp += roundForDisp(res[0]);
        outcalced += fullstr.charAt(i);
        outcalceddisp += fullstr.charAt(i);
      }
      lastcut = i + 1;
    }
  }
  if (NCdepth != 0) {
    notationok = false;
  }
  if (notationok == false) {
    err = _("Invalid notation") + ". " + err;
  }
  if (qtype === 'algntuple') {
    outcalceddisp = '';
  }
  if (format.match(/generalcomplex/)) {
    outcalced = '';
  }
  return {
    err: err,
    dispvalstr: outcalceddisp,
    submitstr: outcalced
  };
}

function processCalcComplex(fullstr, format) {
  if (format.indexOf('allowplusminus') != -1) {
    fullstr = fullstr.replace(/(.*?)\+\-(.*?)(,|$)/g, '$1+$2,$1-$2$3');
  }
  var err = '';
  var arr = fullstr.split(',');
  var str = '';
  var outstr = '';
  var outstrdisp = '';
  var outarr = [];
  var outarrdisp = [];
  var real, imag, imag2, prep, res;
  for (var cnt = 0; cnt < arr.length; cnt++) {
    res = evalcheckcomplex(arr[cnt], format);
    err += res.err;
    outarrdisp.push(res.outstrdisp);
    if (res.outstr) {
      outarr.push(res.outstr);
    }
  }
  if (format.indexOf("generalcomplex") != -1) {
    return {
      err: err,
      dispvalstr: outarrdisp.join(', ')
    };
  } else {
    return {
      err: err,
      dispvalstr: outarrdisp.join(', '),
      submitstr: outarr.join(',')
    };
  }
}

function processSizedMatrix(qn) {
  var params = allParams[qn];
  var size = params.matrixsize;
  var format = '';
  if (params.calcformat) {
    format = params.calcformat;
  }
  var out = [];
  var outcalc = [];
  var outsub = [];
  var count = 0;
  var str, res;
  var err = '';
  for (var row = 0; row < size[0]; row++) {
    out[row] = [];
    outcalc[row] = [];
    for (var col = 0; col < size[1]; col++) {
      str = document.getElementById('qn' + qn + '-' + count).value;
      str = normalizemathunicode(str);
      if (params.qtype.match(/complex/)) {
        res = evalcheckcomplex(str, format);
        err += res.err;
        outcalc[row][col] = res.outstrdisp;
        if (res.outstr) {
          outsub.push(res.outstr);
        }
      } else if (params.qtype === 'algmatrix') {
        res = processNumfunc(qn, str, format);
        err += res.err;
      } else {
        if (str !== '') {
          err += syntaxcheckexpr(str, format);
          err += singlevalsyntaxcheck(str, format);
        }
        out[row][col] = str;
        res = singlevaleval(str, format);
        err += res[1];
        outcalc[row][col] = res[0];
        outsub.push(res[0]);
      }
      count++;
    }
    out[row] = '(' + out[row].join(',') + ')';
    outcalc[row] = '(' + roundForDisp(outcalc[row]).join(',') + ')';
  }
  return {
    err: err,
    str: '[' + out.join(',') + ']',
    dispvalstr: (params.qtype.match(/calc/)) ? ('[' + outcalc.join(',') + ']') : '',
    submitstr: outsub.join('|')
  };
}

function processCalcMatrix(qn, fullstr, format, anstype) {
  var okformat = true;
  fullstr = fullstr.replace(/\[/g, '(');
  fullstr = fullstr.replace(/\]/g, ')');
  fullstr = fullstr.replace(/\s+/g, '');
  if (fullstr.length < 2 || fullstr.charAt(0) !== '(' ||
    fullstr.charAt(fullstr.length - 1) !== ')'
  ) {
    okformat = false;
  }
  fullstr = fullstr.substring(1, fullstr.length - 1);

  var err = '';
  var blankerr = '';
  var rowlist = [];
  var lastcut = 0;
  var MCdepth = 0;
  for (var i = 0; i < fullstr.length; i++) {
    if (fullstr.charAt(i) == '(') {
      MCdepth++;
    } else if (fullstr.charAt(i) == ')') {
      MCdepth--;
    } else if (fullstr.charAt(i) == ',' && MCdepth == 0) {
      rowlist.push(fullstr.substring(lastcut + 1, i - 1));
      lastcut = i + 1;
    }
  }
  if (lastcut == 0 && fullstr.charAt(0) != '(') {
    rowlist.push(fullstr);
  } else {
    rowlist.push(fullstr.substring(lastcut + 1, fullstr.length - 1));
  }
  var lastnumcols = -1;
  if (MCdepth !== 0) {
    okformat = false;
  }
  var collist, str, res;
  var outcalc = [];
  var outsub = [];
  for (var i = 0; i < rowlist.length; i++) {
    outcalc[i] = [];
    collist = rowlist[i].split(',');
    if (lastnumcols > -1 && collist.length != lastnumcols) {
      okformat = false;
    }
    lastnumcols = collist.length;
    for (var j = 0; j < collist.length; j++) {
      str = collist[j].replace(/^\s+/, '').replace(/\s+$/, '');
      if (str == '') {
        blankerr = _('No elements of the matrix should be left blank.');
        outcalc[i][j] = '';
        outsub.push('');
      } else if (anstype === 'algmatrix') {
        res = processNumfunc(qn, str, format);
        err += res.err;
      } else if (anstype.match(/complex/)) {
        res = evalcheckcomplex(str, format);
        err += res.err;
        outcalc[i][j] = res.outstrdisp;
        if (res.outstr) {
          outsub.push(res.outstr);
        }
      } else {
        err += syntaxcheckexpr(str, format);
        err += singlevalsyntaxcheck(str, format);
        res = singlevaleval(str, format);
        err += res[1];
        outcalc[i][j] = roundForDisp(res[0]);
        outsub.push(res[0]);
      }
    }
    outcalc[i] = '(' + outcalc[i].join(',') + ')';
  }
  if (!okformat) {
    err = _('Invalid matrix format') + '. ';
  }
  err += blankerr;
  return {
    err: err,
    dispvalstr: '[' + outcalc.join(',') + ']',
    submitstr: outsub.join('|')
  };
}

//vars and fvars are arrays; format is string
function processNumfunc(qn, fullstr, format) {
  var params = allParams[qn];
  var vars = params.vars;
  var fvars = params.fvars;
  var domain = params.domain;
  var iseqn = format.match(/equation/);
  var isdoubleineq = format.match(/doubleinequality/);
  var isineq = format.match(/inequality/);
  var err = '';
  var primes = [2, 3, 5, 7, 11, 13, 17, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97];

  var strprocess = AMnumfuncPrepVar(qn, fullstr);

  var totesteqn;
  var totestarr;
  if (format.match(/list/)) {
    totestarr = strprocess[0].split(/,/);
  } else {
    if (!commasep && strprocess[0].match(/,/)) {
      err += _("Invalid use of a comma.");
    }
    totestarr = [strprocess[0]];
  }
  var i, j, totest, testval, res;
  var successfulEvals = 0;
  for (var tti = 0; tti < totestarr.length; tti++) {
    totesteqn = totestarr[tti];
    totesteqn = totesteqn.replace(/,/g, "").replace(/^\s+/, '').replace(/\s+$/, '').replace(/degree/g, '');
    var remapVars = strprocess[2].split('|');

    if (totesteqn.match(/(<=|>=|<|>|!=)/)) {
      if (!isineq) {
        if (iseqn) {
          err += _("syntax error: you gave an inequality, not an equation") + '. ';
        } else {
          err += _("syntax error: you gave an inequality, not an expression") + '. ';
        }
      } else if (totesteqn.match(/(<=|>=|<|>|!=)/g).length > 1 && !isdoubleineq) {
        err += _("syntax error: your inequality should only contain one inequality symbol") + '. ';
      } else if (totesteqn.match(/(<=|>=|<|>|!=)/g).length != 2 && isdoubleineq) {
        err += _("syntax error: your inequality should contain two inequality symbols") + '. ';
      } else if (totesteqn.match(/(^(<|>|!))|(=|>|<)$/)) {
        err += _("syntax error: your inequality should have expressions on both sides") + '. ';
      }
      totesteqn = totesteqn.replace(/(.*)(<=|>=|<|>|!=)(.*)/, "$1-($3)");
    } else if (totesteqn.match(/=/)) {
      if (isineq && !iseqn) {
        err += _("syntax error: you gave an equation, not an inequality") + '. ';
      } else if (!iseqn) {
        err += _("syntax error: you gave an equation, not an expression") + '. ';
      } else if (totesteqn.match(/=/g).length > 1) {
        err += _("syntax error: your equation should only contain one equal sign") + '. ';
      } else if (totesteqn.match(/(^=)|(=$)/)) {
        err += _("syntax error: your equation should have expressions on both sides") + '. ';
      }
      totesteqn = totesteqn.replace(/(.*)=(.*)/, "$1-($2)");
    } else if (iseqn && isineq) {
      err += _("syntax error: this is not an equation or inequality") + '. ';
    } else if (iseqn) {
      err += _("syntax error: this is not an equation") + '. ';
    } else if (isineq) {
      err += _("syntax error: this is not an inequality") + '. ';
    }
    if (!format.match(/generalcomplex/)) {
      var parser = makeMathFunction(totesteqn, remapVars.join('|'), [], fvars.join('|'), format.match(/generalcomplex/));
      successfulEvals = 0;
      var mult, loc;
      if (parser !== false) {
        for (j = 0; j < 20; j++) {
          totest = { DNE: 1 };
          for (i = 0; i < remapVars.length - 1; i++) {  // -1 to skip DNE pushed to end
            mult = primes[i % (primes.length)];
            if (j == 0 || j == 19) {
              loc = j / 19;
            } else {
              loc = ((j * mult) % 19) / 20;
            }
            if (domain[i][2]) { //integers
              //testval = Math.floor(Math.random()*(domain[i][0] - domain[i][1] + 1) + domain[i][0]);
              testval = Math.floor(domain[i][0] + (domain[i][1] - domain[i][0]) * loc);
            } else { //any real between min and max
              //testval = Math.random()*(domain[i][1] - domain[i][0]) + domain[i][0];
              testval = domain[i][0] + (domain[i][1] - domain[i][0]) * loc;
            }
            totest[remapVars[i]] = testval;
          }
          res = parser(totest);
          if (res !== '' && !isNaN(res)) {
            successfulEvals++;
            break;
          }
        }
      }
      if (successfulEvals === 0) {
        err += _("syntax error") + '. ';
      }
    }
    err += syntaxcheckexpr(strprocess[0], format + ',isnumfunc', vars.map(escapeRegExp).join('|'));
  }
  return {
    err: err
  };
}

function processMolecule(qn) {
  var mol = allKekule[qn].exportObjs(Kekule.Molecule)[0];
  if (typeof mol === 'undefined') {
    document.getElementById("qn" + qn).value = '';
  } else {
    var smi = Kekule.IO.saveFormatData(mol, 'smi');
    var cml = Kekule.IO.saveFormatData(mol, 'cml');
    document.getElementById("qn" + qn).value = smi + '~~~' + cml;
  }
}
/**
 * Formats the string for rendering
 */
function preformat(qn, text, qtype, calcformat) {
  text = normalizemathunicode(text);
  if (qtype.match(/interval/)) {
    if (!calcformat.match(/inequality/)) {
      text = text.replace(/U/g, "uu");
    } else {
      text = AMnumfuncPrepVar(qn, text)[1];
      text = text.replace(/<=/g, ' le ').replace(/>=/g, ' ge ').replace(/</g, ' lt ').replace(/>/g, ' gt ');
      if (text.match(/all\s*real/i)) {
        text = "text(" + text + ")";
      }
    }
  } else if (qtype == 'numfunc') {
    text = AMnumfuncPrepVar(qn, text)[1];
  } else if (qtype == 'calcntuple') {
    text = text.replace(/<+/g, '(:').replace(/>+/g, ':)');
  } else if (qtype == 'calculated') {
    if (calcformat.indexOf('list') == -1 && calcformat.indexOf('set') == -1 && commasep) {
      text = text.replace(/(\d)\s*,\s*(?=\d{3}\b)/g, "$1");
    }
    if (calcformat.indexOf('scinot') != -1) {
      text = text.replace(/(x|X|\u00D7)/, "xx");
    }
  }
  text = text.replace(/[^\u0000-\u007f]/g, '?');
  return text;
}

function normalizemathunicode(str) {
  str = str.replace(/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/g, "");
  str = str.replace(/\u2013|\u2014|\u2015|\u2212/g, "-");
  str = str.replace(/\u2044|\u2215/g, "/");
  str = str.replace(/∞/g, "oo").replace(/≤/g, "<=").replace(/≥/g, ">=").replace(/∪/g, "U");
  str = str.replace(/±/g, "+-").replace(/÷/g, "/").replace(/·|✕|×|⋅/g, "*");
  str = str.replace(/√/g, "sqrt").replace(/∛/g, "root(3)");
  str = str.replace(/⁰/g, "^0").replace(/¹/g, "^1").replace(/²/g, "^2").replace(/³/g, "^3").replace(/⁴/g, "^4").replace(/⁵/g, "^5").replace(/⁶/g, "^6").replace(/⁷/g, "^7").replace(/⁸/g, "^8").replace(/⁹/g, "^9");
  str = str.replace(/\u2329/g, "<").replace(/\u232a/g, ">");
  str = str.replace(/₀/g, "_0").replace(/₁/g, "_1").replace(/₂/g, "_2").replace(/₃/g, "_3");
  str = str.replace(/\b(OO|infty)\b/gi, "oo").replace(/°/g, 'degree');
  str = str.replace(/θ/g, "theta").replace(/ϕ/g, "phi").replace(/φ/g, "phi").replace(/π/g, "pi").replace(/σ/g, "sigma").replace(/μ/g, "mu");
  str = str.replace(/α/g, "alpha").replace(/β/g, "beta").replace(/γ/g, "gamma").replace(/δ/g, "delta").replace(/ε/g, "epsilon").replace(/κ/g, "kappa");
  str = str.replace(/λ/g, "lambda").replace(/ρ/g, "rho").replace(/τ/g, "tau").replace(/χ/g, "chi").replace(/ω/g, "omega");
  str = str.replace(/Ω/g, "Omega").replace(/Γ/g, "Gamma").replace(/Φ/g, "Phi").replace(/Δ/g, "Delta").replace(/Σ/g, "Sigma");
  str = str.replace(/&(ZeroWidthSpace|nbsp);/g, ' ').replace(/\u200B/g, ' ');
  str = str.replace(/degree\s+s\b/g, 'degree');
  // remove extra parens on numbers, like roots and logs
  str = str.replace(/\(\((-?\d+)\)\)/g, '($1)');
  return str;
}

function singlevalsyntaxcheck(str, format) {
  if (commasep) {
    str = str.replace(/(\d)\s*,\s*(?=\d{3}\b)/g, "$1");
  }
  if (str.match(/DNE/i)) {
    return '';
  } else if (str.match(/-?\+?oo$/) || str.match(/-?\+?oo\W/)) {
    return '';
  } else if (str.match(/,/)) {
    return _("Invalid use of a comma.");
  } else if (format.indexOf('allowmixed') != -1 &&
    str.match(/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))\s*$/)) {
    //if allowmixed and it's mixed, stop checking
    return '';
  } else if (format.indexOf('fracordec') != -1) {
    str = str.replace(/([0-9])\s+([0-9])/g, "$1*$2").replace(/\s/g, '');
    if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\-?\d+$/) && !str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
      return (_(" invalid entry format") + ". ");
    }
  } else if (format.indexOf('fraction') != -1 || format.indexOf('reducedfraction') != -1) {
    str = str.replace(/([0-9])\s+([0-9])/g, "$1*$2").replace(/\s/g, '');
    // if (!str.match(/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
    if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
      return (_("not a valid fraction") + ". ");
    }
  } else if (format.indexOf('mixednumber') != -1) {
    if (!str.match(/^\(?\-?\s*\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))\s*$/) && !str.match(/^\s*\-?\s*\d+\s*$/)) {
      return (_("not a valid mixed number") + ". ");
    }
    str = str.replace(/_/, ' ');
  } else if (format.indexOf('scinot') != -1) {
    str = str.replace(/\s/g, '');
    str = str.replace(/(xx|x|X|\u00D7)/, "xx");
    if (!str.match(/^\-?[1-9](\.\d*)?(\*|xx)10\^(\(?\(?\-?\d+\)?\)?)$/)) {
      if (format.indexOf('scinotordec') == -1) { //not scinotordec
        return (_("not valid scientific notation") + ". ");
      } else if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/)) {
        return (_("not valid decimal or scientific notation") + ". ");
      }
    }
  } else if (format.indexOf('decimal') != -1 && format.indexOf('nodecimal') == -1) {
    if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/)) {
      return (_(" not a valid integer or decimal number") + ". ");
    }
  } else if (format.indexOf('integer') != -1) {
    if (!str.match(/^\s*\-?\d+(\.0*)?\s*$/)) {
      return (_(" not an integer number") + ". ");
    }
  } else if (!onlyAscii.test(str)) {
    return _("Your answer contains an unrecognized symbol") + ". ";
  }
  return '';
}

function syntaxcheckexpr(str, format, vl) {
  var err = '';
  if (format.indexOf('notrig') != -1 && str.match(/(sin|cos|tan|cot|sec|csc)/i)) {
    err += _("no trig functions allowed") + ". ";
  } else if (format.indexOf('nodecimal') != -1 && str.indexOf('.') != -1) {
    err += _("no decimals allowed") + ". ";
  } else if (format.indexOf('mixed') == -1 &&
    str.match(/\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/)) {
    err += _("mixed numbers are not allowed") + ". ";
  } else if (format.indexOf('allowdegrees') == -1 && str.match(/degree/)) {
    err += _("no degree symbols allowed") + ". ";
  }
  var Pdepth = 0; var Bdepth = 0; var Adepth = 0;
  for (var i = 0; i < str.length; i++) {
    if (str.charAt(i) == '(') {
      Pdepth++;
    } else if (str.charAt(i) == ')') {
      Pdepth--;
    } else if (str.charAt(i) == '[') {
      Bdepth++;
    } else if (str.charAt(i) == ']') {
      Bdepth--;
    } else if (str.charAt(i) == '|') {
      Adepth = 1 - Adepth;
    }
  }
  if (Pdepth != 0 || Bdepth != 0) {
    err += " (" + _("unmatched parens") + "). ";
  }
  if (Adepth != 0) {
    err += " (" + _("unmatched absolute value bars") + "). ";
  }
  if (vl) {
    reg = new RegExp("(sqrt|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*(" + vl + "|\\d+)", "i");
  } else {
    reg = new RegExp("(sqrt|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*(\\d+)", "i");
  }
  errstuff = str.match(reg);
  if (errstuff != null) {
    err += "[" + _("use function notation") + " - " + _("use $1 instead of $2", errstuff[1] + "(" + errstuff[2] + ")", errstuff[0]) + "]. ";
  }
  if (vl) {
    if (format.match(/casesensitivevars/)) {
      var reglist = 'degree|arc|arg|ar|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo'.split('|');
      reglist.sort(function (x, y) { return y.length - x.length; });
      let reg1 = new RegExp("(" + reglist.join('|') + ")", "ig");
      var reglist = vl.split('|');
      reglist.sort(function (x, y) { return y.length - x.length; });
      let reg2 = new RegExp("(" + reglist.join('|') + ")", "g");
      if (str.replace(/repvars\d+/g, '').replace(reg1, '').replace(reg2, '').match(/[a-zA-Z]/)) {
        err += _(" Check your variables - you might be using an incorrect one") + ". ";
      }
    } else {
      var reglist = 'degree|arc|arg|ar|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo'.split('|').concat(vl.split('|'));
      reglist.sort(function (x, y) { return y.length - x.length; });
      let reg = new RegExp("(" + reglist.join('|') + ")", "ig");
      if (str.replace(/repvars\d+/g, '').replace(reg, '').match(/[a-zA-Z]/)) {
        err += _(" Check your variables - you might be using an incorrect one") + ". ";
      }
    }

  }
  if ((str.match(/\|/g) || []).length > 2) {
    var regex = /\|.*?\|\s*(.|$)/g;
    while (match = regex.exec(str)) {
      if (match[1] != "" && match[1].match(/[^+\-\*\/\^\)]/)) {
        err += _(" You may want to use abs(x) instead of |x| for absolute values to avoid ambiguity") + ". ";
        break;
      }
    }
  }
  if (str.match(/\(\s*\)/)) {
    err += _(" Empty function input or parentheses") + ". ";
  }
  if (str.match(/%/) && !str.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {
    err += _(" Do not use the percent symbol, %") + ". ";
  }
  if (str.match(/=/) && !format.match(/isnumfunc/)) {
    err += _("You gave an equation, not an expression") + '. ';
  }

  return err;
}

// returns [numval, errmsg]
function singlevaleval(evalstr, format) {
  if (commasep) {
    evalstr = evalstr.replace(/(\d)\s*,\s*(?=\d{3}\b)/g, "$1");
  }
  if (evalstr.match(/,/)) {
    return [NaN, _("syntax incomplete") + ". "];
  }
  if (evalstr.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {//single percent
    evalstr = evalstr.replace(/%/g, '') + '/100';
  }
  if (format.indexOf('mixed') != -1) {
    evalstr = evalstr.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g, "($1+$2/$3)");
  }
  if (format.indexOf('allowxtimes') != -1) {
    evalstr = evalstr.replace(/(xx|x|X|\u00D7)/, "*");
  }
  if (format.indexOf('scinot') != -1) {
    evalstr = evalstr.replace("xx", "*");
  }
  try {
    var res = evalMathParser(evalstr);
    if (isNaN(res) || res === '') {
      return [NaN, _("syntax incomplete") + ". "];
    }
    return [res, ''];
  } catch (e) {
    return [NaN, _("syntax incomplete") + ". "];
  }
}

function escapeRegExp(string) {
  return string.replace(/[.*+\-?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}

/**
 * Rounds values for showval display to 4 decimal places or 4 sigfigs, no trailing zeros.
 * @param number|array vals
 * @returns
 */
function roundForDisp(val) {
  if (Array.isArray(val)) {
    return val.map(roundForDisp);
  } else if (typeof val == 'number') {
    if (Math.abs(val) < 1) {
      return val.toPrecision(4).replace(/\.?0+$/, '');
    } else {
      return val.toFixed(4).replace(/\.?0+$/, '');
    }
  } else {
    return val;
  }
}


//Function to convert inequalities into interval notation
function ineqtointerval(strw, intendedvar) {
  var simpvar = simplifyVariable(intendedvar);
  if (commasep) {
    strw = strw.replace(/(\d)\s*,\s*(?=\d{3}\b)/g, "$1");
  }
  if (strw.match(/all\s*real/i)) {
    return ['(-oo,oo)'];
  } else if (strw.match(/DNE/)) {
    return ['DNE'];
  }
  var pat, interval, out = [];
  var strpts = strw.split(/\s*or\s*/);
  if (strpts.length == 1 && strw.match(/!=/)) {
    var ineqpts = strw.split(/!=/);
    if (ineqpts.length != 2) {
      return ['', 'invalid'];
    } else if (simplifyVariable(ineqpts[0]) != simpvar) {
      return ['', 'wrongvar'];
    }
    return ['(-oo,' + ineqpts[1] + ')U(' + ineqpts[1] + ',oo)'];
  }
  for (var i = 0; i < strpts.length; i++) {
    str = strpts[i];
    if (pat = str.match(/^(.*?)(<=?|>=?)(.*?)(<=?|>=?)(.*?)$/)) {
      if (simplifyVariable(pat[3]) != simpvar) { // wrong var
        return ['', 'wrongvar'];
      } else if (pat[2].charAt(0) != pat[4].charAt(0)) { // mixes > and <
        return ['', 'invalid'];
      } else if (pat[1].trim() == '' || pat[5].trim() == '') {
        return ['', 'invalid'];
      }
      if (pat[2].charAt(0) == '<') {
        interval = (pat[2] == '<' ? '(' : '[') + pat[1] + ',' + pat[5] + (pat[4] == '<' ? ')' : ']');
      } else {
        interval = (pat[4] == '>' ? '(' : '[') + pat[5] + ',' + pat[1] + (pat[2] == '>' ? ')' : ']');
      }
      out.push(interval);
    } else if (pat = str.match(/^(.*?)(<=?|>=?)(.*?)$/)) {
      if (simplifyVariable(pat[1]) == simpvar) { // x> or x<
        if (pat[2].charAt(0) == '<') { // x<
          interval = '(-oo,' + pat[3] + (pat[2] == '<' ? ')' : ']');
        } else { // x>
          interval = (pat[2] == '>' ? '(' : '[') + pat[3] + ',oo)';
        }
        out.push(interval);
      } else if (simplifyVariable(pat[3]) == simpvar) { // 3<x or 3>x
        if (pat[2].charAt(0) == '<') { // 3<x
          interval = (pat[2] == '<' ? '(' : '[') + pat[1] + ',oo)';
        } else { // x>
          interval = '(-oo,' + pat[1] + (pat[2] == '>' ? ')' : ']');
        }
        out.push(interval);
      } else {
        return ['', 'wrongvar'];
      }
    } else {
      return ['', 'invalid'];
    }
  }
  var outstr = out.join("U");
  if (outstr.match(/[\(\[],|,[\)\]]/)) { // catch "x > " without a value
    return ['', 'invalid'];
  }
  return [outstr];
}

function AMnumfuncPrepVar(qn, str) {
  var vars, fvarslist = '';
  if (typeof allParams[qn].vars === 'string') {
    vars = [allParams[qn].vars];
  } else {
    vars = allParams[qn].vars.slice();
  }

  var vl = vars.map(escapeRegExp).join('|');
  if (allParams[qn].fvars) {
    fvarslist = allParams[qn].fvars.map(escapeRegExp).join('|');
  }
  vars.push("DNE");

  if (vl.match(/lambda/)) {
    str = str.replace(/lamda/, 'lambda');
  }

  var foundaltcap = [];
  var dispstr = str;

  dispstr = dispstr.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|argsinh|argcosh|argtanh|argsech|argcsch|argcoth|arsinh|arcosh|artanh|arsech|arcsch|arcoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root|pi)/g, functoindex);
  str = str.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|argsinh|argcosh|argtanh|argsech|argcsch|argcoth|arsinh|arcosh|artanh|arsech|arcsch|arcoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root|pi)/g, functoindex);
  for (var i = 0; i < vars.length; i++) {
    // handle double parens
    if (vars[i].match(/\(.+\)/)) { // variable has parens, not funcvar
      str = str.replace(/\(\(([^\(]*?)\)\)/g, '($1)');
    }
    if (vars[i] == "E" || vars[i] == "e") {
      foundaltcap[i] = true;  // always want to treat e and E as different
    } else {
      foundaltcap[i] = allParams[qn].calcformat.match(/casesensitivevars/); // default false unless casesensitivevars is used
      for (var j = 0; j < vars.length; j++) {
        if (i != j && vars[j].toLowerCase() == vars[i].toLowerCase() && vars[j] != vars[i]) {
          foundaltcap[i] = true;
          break;
        }
      }
    }
  }
  //sequentially escape variables from longest to shortest, then unescape
  str = str.replace(new RegExp("(" + vl + ")", "gi"), function (match, p1) {
    for (var i = 0; i < vars.length; i++) {
      if (vars[i] == p1 || (!foundaltcap[i] && vars[i].toLowerCase() == p1.toLowerCase())) {
        return '@v' + i + '@';
      }
    }
    return p1;
  });
  str = str.replace(/@v(\d+)@/g, function (match, contents) {
    return vars[contents];
  });
  dispstr = dispstr.replace(new RegExp("(" + vl + ")", "gi"), function (match, p1) {
    for (var i = 0; i < vars.length; i++) {
      if (vars[i] == p1 || (!foundaltcap[i] && vars[i].toLowerCase() == p1.toLowerCase())) {
        return '@v' + i + '@';
      }
    }
    return p1;
  });
  // fix variable pairs being interpreted as asciimath symbol, like in
  dispstr = dispstr.replace(/(@v\d+@)(@v\d+@)/g, "$1 $2");
  dispstr = dispstr.replace(/(@v\d+@)(@v\d+@)/g, "$1 $2");
  // fix display of /n!
  dispstr = dispstr.replace(/(@v(\d+)@|\d+(\.\d+)?)!(?!=)/g, '{:$&:}');
  dispstr = dispstr.replace(/@v(\d+)@/g, function (match, contents) {
    return vars[contents];
  });

  var submitstr = str;
  //quote out multiletter variables
  var varstoquote = new Array(); var regmod;
  for (var i = 0; i < vars.length; i++) {
    if (vars[i].length > 1) {
      var isgreek = false;
      if (greekletters.indexOf(vars[i].toLowerCase()) != -1) {
        isgreek = true;
      }
      if (vars[i].match(/^\w+_\w+$/)) {
        if (!foundaltcap[i]) {
          regmod = "gi";
        } else {
          regmod = "g";
        }
        //var varpts = vars[i].match(new RegExp(/^(\w+)_(\d*[a-zA-Z]+\w+)$/,regmod));
        var varpts = new RegExp(/^(\w+)_(\w+)$/, regmod).exec(vars[i]);
        var remvarparen = new RegExp(varpts[1] + '_\\(' + varpts[2] + '\\)', regmod);
        dispstr = dispstr.replace(remvarparen, vars[i]);
        str = str.replace(remvarparen, vars[i]);
        submitstr = submitstr.replace(new RegExp(varpts[0], regmod), varpts[1] + '_' + varpts[2]);
        submitstr = submitstr.replace(
          new RegExp(varpts[1] + '_\\(' + varpts[2] + '\\)', regmod),
          varpts[1] + '_(' + varpts[2] + ')');
        if (varpts[1].length > 1 && greekletters.indexOf(varpts[1].toLowerCase()) == -1) {
          varpts[1] = '"' + varpts[1] + '"';
        }
        if (varpts[2].length > 1 && greekletters.indexOf(varpts[2].toLowerCase()) == -1) {
          varpts[2] = '"' + varpts[2] + '"';
        }
        dispstr = dispstr.replace(new RegExp(varpts[0], regmod), varpts[1] + '_' + varpts[2]);
        //this repvars was needed to workaround with mathjs confusion with subscripted variables
        str = str.replace(new RegExp(varpts[0], "g"), " repvars" + i);
        vars[i] = "repvars" + i;
      } else if (!isgreek && vars[i].replace(/[^\w_]/g, '').length > 1) {
        varstoquote.push(vars[i]);
      }
      if (vars[i].match(/[^\w_]/) || vars[i].match(/^(break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|and with)$/)) {
        str = str.replace(new RegExp(escapeRegExp(vars[i]), "g"), " repvars" + i);
        vars[i] = "repvars" + i;
      }
    }
  }

  if (varstoquote.length > 0) {
    vltq = varstoquote.join("|");
    var reg = new RegExp("(" + vltq + ")", "g");
    dispstr = dispstr.replace(reg, "\"$1\"");
  }
  dispstr = dispstr.replace(/(@\d+@)/g, " $1");
  dispstr = dispstr.replace(/@(\d+)@/g, indextofunc);
  str = str.replace(/@(\d+)@/g, indextofunc);
  submitstr = submitstr.replace(/@(\d+)@/g, indextofunc);

  //Correct rendering when f or g is a variable not a function
  if (vl.match(/\bf\b/) && !fvarslist.match(/\bf\b/)) {
    dispstr = dispstr.replace(/([^a-zA-Z])f\^([\d\.]+)([^\d\.])/g, "$1f^$2{::}$3");
    dispstr = dispstr.replace(/([^a-zA-Z])f\(/g, "$1f{::}(");
  }
  if (vl.match(/\bg\b/) && !fvarslist.match(/\bg\b/)) {
    dispstr = dispstr.replace(/([^a-zA-Z])g\^([\d\.]+)([^\d\.])/g, "$1g^$2{::}$3");
    dispstr = dispstr.replace(/([^a-zA-Z])g\(/g, "$1g{::}(");
  }
  return [str, dispstr, vars.join("|"), submitstr];
}


function evalcheckcomplex(str, format) {
  var err = '';
  var out = '';
  var outstr = '';
  var outstrdisp = '';
  str = str.replace(/^\s+/, '').replace(/\s+$/, '');
  if (format.indexOf("allowjcomplex") != -1) {
    str = str.replace(/j/g, 'i');
  }
  // general check
  err += syntaxcheckexpr(str, format);
  if (format.indexOf("generalcomplex") != -1) {
    // no eval
    return {
      err: err,
      outstrdisp: str
    };
  } else if (format.indexOf("sloppycomplex") == -1) {
    // regular a+bi complex; check formats
    var cparts = parsecomplex(str);
    if (typeof cparts == 'string') {
      err += cparts;
    } else {
      err += singlevalsyntaxcheck(cparts[0], format);
      err += singlevalsyntaxcheck(cparts[1], format);
    }
  }

  // evals
  if (str !== '') {
    var res = evalMathParser(str, true);
    if (!Array.isArray(res)) {
      err += _("syntax incomplete");
      real = NaN;
    } else {
      var real = res[0];
      var imag = res[1];
      outstr = Math.abs(real) < 1e-16 ? '' : real;
      outstrdisp = Math.abs(real) < 1e-16 ? '' : roundForDisp(real);
      outstr += Math.abs(imag) < 1e-16 ? '' : ((imag > 0 && outstr != '' ? '+' : '') + imag + 'i');
      outstrdisp += Math.abs(imag) < 1e-16 ? '' : ((imag > 0 && outstr != '' ? '+' : '') + roundForDisp(imag) + 'i');
    }
  }
  return {
    err: err,
    outstrdisp: outstrdisp,
    outstr: outstr
  };
}
