M.gradingform_erubric = {};

/**
 * This function is called for each e-rubric on page.
 */
M.gradingform_erubric.init = function(Y, options) {
    Y.on('click', M.gradingform_erubric.levelclick, '#erubric-'+options.name+' .level', null, Y, options.name);
    Y.all('#erubric-'+options.name+' .radio').setStyle('display', 'none')
    Y.all('#erubric-'+options.name+' .level').each(function (node) {
    // Only for not enriched levels.
    if (!node.hasClass('currentenenriched') && node.one('input[type=radio]').get('checked')) {
        node.addClass('checked');
    }
    });
};

M.gradingform_erubric.levelclick = function(e, Y, name) {
    var el = e.target
    while (el && !el.hasClass('level')) el = el.get('parentNode')
    if (!el) return
    // If this level is already enriched, return.
    if (el.hasClass('currentenenriched')) return
    
    e.preventDefault();
    el.siblings().removeClass('checked');
    chb = el.one('input[type=radio]')
    if (!chb.get('checked')) {
        chb.set('checked', true)
        el.addClass('checked')
    } else {
        el.removeClass('checked');
        el.get('parentNode').all('input[type=radio]').set('checked', false)
    }
}