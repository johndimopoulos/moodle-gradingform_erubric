M.gradingform_erubriceditor = {'enrichedconst' : {}, 'templates' : {}, 'eventhandler' : null, 'richevents' : [], 'name' : null, 'Y' : null};

/**
 * This function is called for each enriched rubriceditor on page.
 */
M.gradingform_erubriceditor.init = function(Y, options) {
    M.gradingform_erubriceditor.name = options.name
    M.gradingform_erubriceditor.Y = Y
    M.gradingform_erubriceditor.templates[options.name] = {
        'criterion' : options.criteriontemplate,
        'enrichedcriterion' : options.enrichedcriteriontemplate,
        'level' : options.leveltemplate,
        'enrichedlevel' : options.enrichedleveltemplate
    }
    // Get predefined variables from erubriceditor.php.
    M.gradingform_erubriceditor.enrichedconst[options.name] = {
        'selectactivity' : options.interactiontypecollaboration,
        'selectassignment' : options.interactiontypegrade,
        'selectresource' : options.interactiontypestudy,
        'referenceabsolutenumber' : options.referencestudent,
        'referencepercentage' : options.referencestudents,
        'collaborationpeople' : options.collaborationpeople,
        'collaborationfiles' : options.collaborationfiles,
        'modulesicons' : options.moduleicon
    }
    M.gradingform_erubriceditor.disablealleditors()
    Y.on('click', M.gradingform_erubriceditor.clickanywhere, 'body', null)
    YUI().use('event-touch', function (Y) {
        Y.one('body').on('touchstart', M.gradingform_erubriceditor.clickanywhere);
        Y.one('body').on('touchend', M.gradingform_erubriceditor.clickanywhere);
    })
    M.gradingform_erubriceditor.addhandlers()
    M.gradingform_erubriceditor.addenrichmenthandlers()
};

// Adds handlers for clicking submit button. This function must be called each time JS adds new elements to html.
M.gradingform_erubriceditor.addhandlers = function() {
    var Y = M.gradingform_erubriceditor.Y
    var name = M.gradingform_erubriceditor.name
    if (M.gradingform_erubriceditor.eventhandler) M.gradingform_erubriceditor.eventhandler.detach()

    M.gradingform_erubriceditor.eventhandler = Y.on('click', M.gradingform_erubriceditor.buttonclick, '#erubric-'+name+' input[type=submit]', null);
}

// Adds handlers for all enriched form elements. This function must be called each time JS adds new elements to html.
M.gradingform_erubriceditor.addenrichmenthandlers = function() {
    var Y = M.gradingform_erubriceditor.Y
    var name = M.gradingform_erubriceditor.name
    if (M.gradingform_erubriceditor.richevents.length){
        for (var i=0; i<M.gradingform_erubriceditor.richevents.length; i++) {
            M.gradingform_erubriceditor.richevents[i].detach()
        }
    }
    // Add multiple handlers for the appropriate elements.
    M.gradingform_erubriceditor.richevents[0] = Y.on('change', M.gradingform_erubriceditor.selectcoursemodule, '#erubric-'+name+' .assignment', null);
    M.gradingform_erubriceditor.richevents[1] = Y.on('change', M.gradingform_erubriceditor.selectcoursemodule, '#erubric-'+name+' .activity', null);
    M.gradingform_erubriceditor.richevents[2] = Y.on('change', M.gradingform_erubriceditor.selectcoursemodule, '#erubric-'+name+' .resource', null);
    M.gradingform_erubriceditor.richevents[3] = Y.on('change', M.gradingform_erubriceditor.changecriteriontype, '#erubric-'+name+' .criteriontype', null);
    M.gradingform_erubriceditor.richevents[4] = Y.on('change', M.gradingform_erubriceditor.changereferencetypeselect, '#erubric-'+name+' .referencetype', null);
    M.gradingform_erubriceditor.richevents[5] = Y.on('change', M.gradingform_erubriceditor.changereferencetypeselect, '#erubric-'+name+' .collaborationtype', null);
}

// Switches all input text and select elements to non-edit mode.
M.gradingform_erubriceditor.disablealleditors = function() {
    var Y = M.gradingform_erubriceditor.Y
    var name = M.gradingform_erubriceditor.name

    Y.all('#erubric-'+name+' .level').each( function(node) {M.gradingform_erubriceditor.editmode(node, false)} );
    Y.all('#erubric-'+name+' .description').each( function(node) {M.gradingform_erubriceditor.editmode(node, false)} );
    Y.all('#erubric-'+name+' .rich .select').each( function(node) {M.gradingform_erubriceditor.editenrichedselect(node, false)} );
    Y.all('#erubric-'+name+' .enrichedlevel').each( function(node) {M.gradingform_erubriceditor.editrichedvalue(node, false)} );
    Y.all('#erubric-'+name+' .rich .collaborationtype').each( function(node) {M.gradingform_erubriceditor.checkcollaborationtype(node)} );
}

// Function invoked on each click on the page. If level and/or criterion fields are clicked
// it switches this element to edit mode. If rubric button is clicked it does nothing so the 'buttonclick'.
// Function is invoked.
M.gradingform_erubriceditor.clickanywhere = function(e) {
    if (e.type == 'touchstart') return
    var el = e.target
    // If clicked on button - disablecurrenteditor, continue.
    if (el.get('tagName') == 'INPUT' && el.get('type') == 'submit') {
        return
    }
    // Else if clicked on level and this level is not enabled - enable it
    // or if clicked on description and this description is not enabled, enable it.
    var focustb = false
    while (el && !(el.hasClass('level') || el.hasClass('description') || el.hasClass('enrichedlevel') || el.hasClass('rich'))) {
        if (el.hasClass('score')) focustb = true
        el = el.get('parentNode')
    }
    if (el) {

        // Ccheck if this is an ordinary rubric field.
        if (el.hasClass('level') || el.hasClass('description')){
            if (el.one('textarea').hasClass('hiddenelement')) {
                M.gradingform_erubriceditor.disablealleditors()
                M.gradingform_erubriceditor.editmode(el, true, focustb)
            }
        // Check if this is an enriched select.
        }else if (el.hasClass('rich')) {
            if (el.one('select').hasClass('hiddenelement')) {
                // Enable select fields only if interaction type is selected.
                if (el.one('select').hasClass('criteriontype') || el.get('parentNode').one('.criteriontype').get('value').length){
                    M.gradingform_erubriceditor.disablealleditors()
                    // If this is a course module select, pick the appropriate select field.
                    if (el.hasClass('coursemodule')) {
                        var name = M.gradingform_erubriceditor.name
                        switch (String(el.get('parentNode').one('.criteriontype').get('value'))) { // These values are defined in lib.php.
                            case String(M.gradingform_erubriceditor.enrichedconst[name]['selectactivity']):
                                el = el.one('.activity');
                                break;
                            case String(M.gradingform_erubriceditor.enrichedconst[name]['selectassignment']):
                                el = el.one('.assignment');
                                break;
                            case String(M.gradingform_erubriceditor.enrichedconst[name]['selectresource']):
                                el = el.one('.resource');
                                break;
                        }
                    }
                    else el = el.one('select')
                    M.gradingform_erubriceditor.editenrichedselect(el, true)
                }else if (!el.get('parentNode').one('.criteriontype').get('value').length) M.gradingform_erubriceditor.disablealleditors()
            }
        // Otherwise is an enriched value field.
        } else {
            if (el.one('.enrichedvalue input[type=text]').hasClass('hiddenelement')) {
                // Check if the enrichment of this criterion is engaged.
                var tmp = el.one('.enrichedvalue')
                var chunks = tmp.get('id').split('-'),
                    Y = M.gradingform_erubriceditor.Y,
                    name = M.gradingform_erubriceditor.name

                if (Y.one('#'+name+'-criteria-'+chunks[2]+'-criteriontype').get('value').length) { //if enrichement is engaged, enable field
                    M.gradingform_erubriceditor.disablealleditors()
                    M.gradingform_erubriceditor.editrichedvalue(el, true)
                }
            }
        }
        return
    }
    // Else disablecurrenteditor.
    M.gradingform_erubriceditor.disablealleditors()
}

// Switch the enriched level enriched value to edit mode or switch back.
M.gradingform_erubriceditor.editrichedvalue = function(el, editmode) {

    var richval = el.one('.enrichedvalue input[type=text]')

    if (!editmode && richval.hasClass('hiddenelement')) return;
    if (editmode && !richval.hasClass('hiddenelement')) return;

    var pseudorichvallink = '<input type="text" size="1" class="pseudotablink"/>',
        richvalplain = richval.get('parentNode').one('.plainvalue')

    // Add 'plainvalue' next to select field for value/definition.
    if (!richvalplain) {
        richval.get('parentNode').append('<span class="plainvalue">'+pseudorichvallink+'<span class="textvalue">&nbsp;</span></span>')
        richvalplain = richval.get('parentNode').one('.plainvalue')
        richvalplain.one('.pseudotablink').on('focus', M.gradingform_erubriceditor.clickanywhere)
    }

    if (!editmode) {
        // If we need to hide the input field, copy its contents to plainvalue. If enriched value
        // is empty, display the default text ('Add value') and add/remove 'empty' CSS class to element.
        var value = richval.get('value')
        if (value.length) richvalplain.removeClass('empty')
        else {
            // Give the appropriate empty value according to this field.
            value = M.str.gradingform_erubric.enrichedvalueempty
            richvalplain.addClass('empty')
        }
        richvalplain.one('.textvalue').set('innerHTML', value)

        // Hide/display textbox and plaintexts.
        richvalplain.removeClass('hiddenelement')
        richval.addClass('hiddenelement')
    } else {
        // Hide/display textbox and plaintexts.
        richvalplain.addClass('hiddenelement')
        richval.removeClass('hiddenelement')
        richval.focus()
    }
}

// Switch the criterion enriched select fields to edit mode or switch back.
M.gradingform_erubriceditor.editenrichedselect = function(el, editmode) {

    var sel = el

    if (!editmode && sel.hasClass('hiddenelement')) return;
    if (editmode && !sel.hasClass('hiddenelement')) return;

    var pseudosellink = '<input type="text" size="1" class="pseudosellink"/>',
        selplain = sel.get('parentNode').one('.plainvaluerich'),
        ulfield

    // Add 'plainvaluerich' next to select field for value/definition.
    if (!selplain) {
        sel.get('parentNode').append('<div class="plainvaluerich">'+pseudosellink+'<span class="textvalue">&nbsp;</span></div>')
        selplain = sel.get('parentNode').one('.plainvaluerich')
        selplain.one('.pseudosellink').on('focus', M.gradingform_erubriceditor.clickanywhere)
    }

    // Hide or display ul course modules.
    if (sel.get('parentNode').hasClass('coursemodule')) {
        ulfield = sel.get('parentNode').one('ul')
        if (!ulfield.hasChildNodes()) ulfield.addClass('hiddenelement')
    }

    if (!editmode) {
        // If we need to hide the select fields, copy their selected values to plainvalue(s). If none selected,
        // display the default text according to field and add/remove 'empty' CSS class to element.
        var value = sel.get('value')
        var index = sel.get('selectedIndex');
        var desc = sel.get("options").item(index).get('text');

        if (value.length) selplain.removeClass('empty')
        else {
            // Give the appropriate empty value according to select field.
            if (sel.hasClass('criteriontype')) desc = M.str.gradingform_erubric.intercactionempty
            if (sel.hasClass('collaborationtype')) desc = M.str.gradingform_erubric.collaborationempty
            if (sel.hasClass('activity')||sel.hasClass('assignment')||sel.hasClass('resource')) desc = M.str.gradingform_erubric.coursemoduleempty
            if (sel.hasClass('operator')) desc = M.str.gradingform_erubric.operatorempty
            if (sel.hasClass('referencetype')) desc = M.str.gradingform_erubric.referencetypeempty
            selplain.addClass('empty')
        }
        selplain.one('.textvalue').set('innerHTML', desc)

        // Hide/display select fields and plaintexts.
        selplain.removeClass('hiddenelement')
        sel.addClass('hiddenelement')
    } else {
        // Hide/display textarea, textbox and plaintexts.
        selplain.addClass('hiddenelement')
        sel.removeClass('hiddenelement')
    }
}

// Switch the criterion description or level to edit mode or switch back.
M.gradingform_erubriceditor.editmode = function(el, editmode, focustb) {

    var ta = el.one('textarea')

    if (!editmode && ta.hasClass('hiddenelement')) return;
    if (editmode && !ta.hasClass('hiddenelement')) return;
    var pseudotablink = '<input type="text" size="1" class="pseudotablink"/>',
        taplain = ta.get('parentNode').one('.plainvalue'),
        tbplain = null,
        tb = el.one('.score input[type=text]')

    // Add 'plainvalue' next to textarea for description/definition and next to input text field for score (if applicable).
    if (!taplain) {
        ta.get('parentNode').append('<div class="plainvalue">'+pseudotablink+'<span class="textvalue">&nbsp;</span></div>')
        taplain = ta.get('parentNode').one('.plainvalue')
        taplain.one('.pseudotablink').on('focus', M.gradingform_erubriceditor.clickanywhere)
        if (tb) {
            tb.get('parentNode').append('<span class="plainvalue">'+pseudotablink+'<span class="textvalue">&nbsp;</span></span>')
            tbplain = tb.get('parentNode').one('.plainvalue')
            tbplain.one('.pseudotablink').on('focus', M.gradingform_erubriceditor.clickanywhere)
        }
    }
    if (tb && !tbplain) tbplain = tb.get('parentNode').one('.plainvalue')
    if (!editmode) {
        // if we need to hide the input fields, copy their contents to plainvalue(s). If description/definition
        // is empty, display the default text ('Click to edit ...') and add/remove 'empty' CSS class to element.
        var value = ta.get('value')
        if (value.length) taplain.removeClass('empty')
        else {
            value = (el.hasClass('level')) ? M.str.gradingform_erubric.levelempty : M.str.gradingform_erubric.criterionempty
            taplain.addClass('empty')
        }
        taplain.one('.textvalue').set('innerHTML', Y.Escape.html(value));
        if (tb) tbplain.one('.textvalue').set('innerHTML', Y.Escape.html(tb.get('value')));
        // Hide/display textarea, textbox and plaintexts.
        taplain.removeClass('hiddenelement')
        ta.addClass('hiddenelement')
        if (tb) {
            tbplain.removeClass('hiddenelement')
            tb.addClass('hiddenelement')
        }
    } else {
        // If we need to show the input fields, set the width/height for textarea so it fills the cell.
        try {
            var width = parseFloat(ta.get('parentNode').getComputedStyle('width')),
                height
            if (el.hasClass('level')) height = parseFloat(el.getComputedStyle('height')) - parseFloat(el.one('.score').getComputedStyle('height'))
            else height = parseFloat(ta.get('parentNode').getComputedStyle('height'))
            ta.setStyle('width', Math.max(width-16,50)+'px')
            ta.setStyle('height', Math.max(height,20)+'px')
        }
        catch (err) {
            // This browser do not support 'computedStyle', leave the default size of the textbox.
        }
        // Hide/display textarea, textbox and plaintexts.
        taplain.addClass('hiddenelement')
        ta.removeClass('hiddenelement')
        if (tb) {
            tbplain.addClass('hiddenelement')
            tb.removeClass('hiddenelement')
        }
    }
    // Focus the proper input field in edit mode.
    if (editmode) { if (tb && focustb) tb.focus(); else ta.focus() }
}

// Handler for clicking on submit buttons within rubriceditor element. Adds/deletes/rearranges criteria and/or levels on client side.
M.gradingform_erubriceditor.buttonclick = function(e, confirmed) {
    var Y = M.gradingform_erubriceditor.Y
    var name = M.gradingform_erubriceditor.name
    if (e.target.get('type') != 'submit') return;
    M.gradingform_erubriceditor.disablealleditors()
    var chunks = e.target.get('id').split('-'),
        action = chunks[chunks.length-1]
    if (chunks[0] != name || chunks[1] != 'criteria') return;
    var elements_str
    var rich_elements_str

    if (chunks.length>4 || action == 'addlevel') {
        elements_str = '#erubric-'+name+' #'+name+'-criteria-'+chunks[2]+'-levels .level'
        rich_elements_str = '#erubric-'+name+' #'+name+'-enriched-criteria-'+chunks[2]+'-levels .enrichedlevel'
    } else {
        elements_str = '#erubric-'+name+' .criterion'
        rich_elements_str = '#erubric-'+name+' .enrichedcriterion'
    }
    // Prepare the id of the next inserted level or criterion.
    if (action == 'addcriterion' || action == 'addlevel') {
        var newid = M.gradingform_erubriceditor.calculatenewid('#erubric-'+name+' .criterion')
        var newlevid = M.gradingform_erubriceditor.calculatenewid('#erubric-'+name+' .level')
    }
    var dialog_options = {
        'scope' : this,
        'callbackargs' : [e, true],
        'callback' : M.gradingform_erubriceditor.buttonclick
    };

    // ADD NEW CRITERION
    if (chunks.length == 3 && action == 'addcriterion') {
        var levelsscores = [0], levidx = 1
        var parentel = Y.one('#'+name+'-criteria')
        if (parentel.one('>tbody')) parentel = parentel.one('>tbody')
        if (parentel.all('.criterion').size()) {
            var lastcriterion = parentel.all('.criterion').item(parentel.all('.criterion').size()-1).all('.level')
            for (levidx=0;levidx<lastcriterion.size();levidx++) levelsscores[levidx] = lastcriterion.item(levidx).one('.score input[type=text]').get('value')
        }
        for (levidx;levidx<3;levidx++) levelsscores[levidx] = parseFloat(levelsscores[levidx-1])+1
        var levelsstr = '';
        var enrichedlevelsstr = '';
        for (levidx=0;levidx<levelsscores.length;levidx++) {
            levelsstr += M.gradingform_erubriceditor.templates[name]['level'].replace(/\{LEVEL-id\}/g, 'NEWID'+(newlevid+levidx)).replace(/\{LEVEL-score\}/g, levelsscores[levidx])
            enrichedlevelsstr += M.gradingform_erubriceditor.templates[name]['enrichedlevel'].replace(/\{LEVEL-id\}/g, 'NEWID'+(newlevid+levidx))
        }
        var newcriterion = M.gradingform_erubriceditor.templates[name]['criterion'].replace(/\{LEVELS\}/, levelsstr)
           // Add the enriched criterion.
           newcriterion += M.gradingform_erubriceditor.templates[name]['enrichedcriterion'].replace(/\{LEVELS\}/, enrichedlevelsstr)
        parentel.append(newcriterion.replace(/\{CRITERION-id\}/g, 'NEWID'+newid).replace(/\{.+?\}/g, ''))
        M.gradingform_erubriceditor.assignclasses('#erubric-'+name+' #'+name+'-criteria-NEWID'+newid+'-levels .level')
        M.gradingform_erubriceditor.assignclasses('#erubric-'+name+' #'+name+'-enriched-criteria-NEWID'+newid+'-levels .enrichedlevel')
        M.gradingform_erubriceditor.addhandlers()
        M.gradingform_erubriceditor.addenrichmenthandlers()
        M.gradingform_erubriceditor.disablealleditors()
        M.gradingform_erubriceditor.assignclasses(elements_str)
        M.gradingform_erubriceditor.assignclasses(rich_elements_str)

        // Add handler for new help icon for enrichment.
        var newhelpicon = Y.one('#erubric-'+name+' #'+name+'-enriched-criteria-NEWID'+newid+' .helptooltip a') // Get the new help icon.
        // Get the id of the last help icon.
        var lastcriterionhelpiconid = parentel.all('.enrichedcriterion').item(parentel.all('.enrichedcriterion').size()-2).one('.helptooltip a').get('id')
        // Subtract the last digit from the id, increase it by one, and re-attach the new value to create the new id for the new help icon.
        // All these are done because all help icons must have different ids, in order for javascript to work when user clicks on them.
        var tempid = lastcriterionhelpiconid.substr(-1)
            tempid++
        var newiconid = lastcriterionhelpiconid.substr(0, lastcriterionhelpiconid.length-1)+tempid
        // Set the new ID to the new help icon.
        newhelpicon.set('id', newiconid)

        // Enable and set focus on the new criterion description field.
        M.gradingform_erubriceditor.editmode(Y.one('#erubric-'+name+' #'+name+'-criteria-NEWID'+newid+'-description'),true)

    // ADD NEW LEVEL
    } else if (chunks.length == 5 && action == 'addlevel') {
        var newscore = 0;
        parent = Y.one('#'+name+'-criteria-'+chunks[2]+'-levels')
        rich_parent = Y.one('#'+name+'-enriched-criteria-'+chunks[2]+'-levels')

        parent.all('.level').each(function (node) { newscore = Math.max(newscore, parseFloat(node.one('.score input[type=text]').get('value'))+1) })
        var newlevel = M.gradingform_erubriceditor.templates[name]['level'].
            replace(/\{CRITERION-id\}/g, chunks[2]).replace(/\{LEVEL-id\}/g, 'NEWID'+newlevid).replace(/\{LEVEL-score\}/g, newscore).replace(/\{.+?\}/g, '')

        var newrichlevel = M.gradingform_erubriceditor.templates[name]['enrichedlevel'].
            replace(/\{CRITERION-id\}/g, chunks[2]).replace(/\{LEVEL-id\}/g, 'NEWID'+newlevid).replace(/\{.+?\}/g, '')

        parent.append(newlevel)
        rich_parent.append(newrichlevel)
        M.gradingform_erubriceditor.addhandlers()
        M.gradingform_erubriceditor.disablealleditors()
        M.gradingform_erubriceditor.assignclasses(elements_str)
        M.gradingform_erubriceditor.assignclasses(rich_elements_str)
        var firstlevelsuffix = rich_parent.get('firstChild').one('i').get('innerHTML')
        // Change the suffix of the enriched value, if needed.
        if (firstlevelsuffix) {
            rich_parent.get('lastChild').one('i').set('innerHTML', firstlevelsuffix)
        }
        // Enable and set focus on the new level description field.
        M.gradingform_erubriceditor.editmode(parent.all('.level').item(parent.all('.level').size()-1), true)

    // MOVE CRITERION UP
    } else if (chunks.length == 4 && action == 'moveup') {
        el = Y.one('#'+name+'-criteria-'+chunks[2])
        rich_el = Y.one('#'+name+'-enriched-criteria-'+chunks[2])

        // Go 2 previous elements over (simple criterion tr and enrichment tr) and make the insert... .
        prev_el = el.previous()
        if (prev_el.previous()) {
            el.get('parentNode').insertBefore(el, prev_el.previous())
            rich_el.get('parentNode').insertBefore(rich_el, prev_el.previous())
        }

        M.gradingform_erubriceditor.assignclasses(elements_str)
        M.gradingform_erubriceditor.assignclasses(rich_elements_str)

    // MOVE CRITERION DOWN
    } else if (chunks.length == 4 && action == 'movedown') {
        el = Y.one('#'+name+'-criteria-'+chunks[2])
        rich_el = Y.one('#'+name+'-enriched-criteria-'+chunks[2])

        // Go 2 next elements under (simple criterion tr and enrichment tr) and make the insert... .
        next_el = el.next()
        if (next_el.next()) {
            el.get('parentNode').insertBefore(next_el.next(), el)
            rich_el.get('parentNode').insertBefore(next_el.next(), el)
        }
        M.gradingform_erubriceditor.assignclasses(elements_str)
        M.gradingform_erubriceditor.assignclasses(rich_elements_str)

    // DELETE CRITERION
    } else if (chunks.length == 4 && action == 'delete') {

        if (confirmed) {
            Y.one('#'+name+'-criteria-'+chunks[2]).remove()
            M.gradingform_erubriceditor.assignclasses(elements_str)
            Y.one('#'+name+'-enriched-criteria-'+chunks[2]).remove()
            M.gradingform_erubriceditor.assignclasses(rich_elements_str)
        } else { // Get and display dialog message.
            dialog_options['message'] = M.str.gradingform_erubric.confirmdeletecriterion
            M.util.show_confirm_dialog(e, dialog_options);
        }

    // DELETE LEVEL
    } else if (chunks.length == 6 && action == 'delete') {

        if (confirmed) {
            Y.one('#'+name+'-criteria-'+chunks[2]+'-'+chunks[3]+'-'+chunks[4]).remove()
            M.gradingform_erubriceditor.assignclasses(elements_str)
            Y.one('#'+name+'-enriched-criteria-'+chunks[2]+'-'+chunks[3]+'-'+chunks[4]).remove()
            M.gradingform_erubriceditor.assignclasses(rich_elements_str)
        } else { // Get and display dialog message.
            dialog_options['message'] = M.str.gradingform_erubric.confirmdeletelevel
            M.util.show_confirm_dialog(e, dialog_options);
        }

    // DELETE COURSE MODULE ACTIVITY/RESOURCE/ASSIGNMENT
    } else if (chunks.length == 7 && action == 'deletemodule') {

        if (confirmed) {
            var litobedeleted = Y.one('#'+name+'-criteria-'+chunks[2]+'-'+chunks[3]+'-'+chunks[4]+'-'+chunks[5])
            var    ul = litobedeleted.get('parentNode')
                litobedeleted.remove()
            // Check if this is the last child and hide the ul.
            if (!ul.hasChildNodes()) {
                ul.addClass('hiddenelement')
            }
        } else { // Get and display dialog message.
            switch(chunks[3]) {
                case 'activity':
                    dialog_options['message'] = M.str.gradingform_erubric.confirmdeleteactivity
                    break;
                case 'resource':
                    dialog_options['message'] = M.str.gradingform_erubric.confirmdeleteresource
                    break;
                case 'assignment':
                    dialog_options['message'] = M.str.gradingform_erubric.confirmdeleteassignment
                    break;
                default:
                    dialog_options['message'] = '  '
            }
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else { // Unknown action.
        return;
    }
    // Don't submit the form.
    e.preventDefault();
}

// Properly set classes (first/last/odd/even), level width and/or criterion sortorder for elements Y.all(elements_str).
M.gradingform_erubriceditor.assignclasses = function (elements_str) {
    var elements = M.gradingform_erubriceditor.Y.all(elements_str)
    for (var i=0;i<elements.size();i++) {
        elements.item(i).removeClass('first').removeClass('last').removeClass('even').removeClass('odd').
            addClass(((i%2)?'odd':'even') + ((i==0)?' first':'') + ((i==elements.size()-1)?' last':''))
        elements.item(i).all('input[type=hidden]').each(
            function(node) {if (node.get('name').match(/sortorder/)) node.set('value', i)}
        );
        if (elements.item(i).hasClass('level')) elements.item(i).set('width', Math.round(100/elements.size())+'%')
        if (elements.item(i).hasClass('enrichedlevel')) elements.item(i).set('width', Math.round(100/elements.size())+'%')
    }
}

// Returns unique id for the next added element, it should not be equal to any of Y.all(elements_str) ids.
M.gradingform_erubriceditor.calculatenewid = function (elements_str) {
    var newid = 1
    M.gradingform_erubriceditor.Y.all(elements_str).each( function(node) {
        var idchunks = node.get('id').split('-'), id = idchunks.pop();
        if (id.match(/^NEWID(\d+)$/)) newid = Math.max(newid, parseInt(id.substring(5))+1);
    } );
    return newid
}

// Selection of course modules handler to add new course modules for the enriched criterion.
// Multiple Select Fields code inspired from Michal Wojciechowski (life saver)
// and was copied from http://odyniec.net/articles/multiple-select-fields/ .
M.gradingform_erubriceditor.selectcoursemodule = function (e) {
    var selfield = e.target,
        ul = selfield.get('parentNode').one('ul'),
        fvalue = selfield.get('value');
    var tempElemnt = ul.one("input[value='" + fvalue + "']")

    // Make changes only if the selected course module isn't already added.
    if (!ul.contains(tempElemnt)) {

        var deletetitle,
            chunks = selfield.get('id').split('-'),
            name = M.gradingform_erubriceditor.name,
            fname = selfield.get('name'),
            fid = selfield.get('id'),
            findex = selfield.get('selectedIndex'),
            fdesc = selfield.get("options").item(findex).get('text'),
            ficon = '',
            lititle = '', delbtn = '',
            Y = M.gradingform_erubriceditor.Y

        if (selfield.hasClass('activity')) {

            var collaborationtypevalue = String(selfield.get('parentNode').get('parentNode').get('parentNode').one('.collaborationtype').get('value'))

            if (!collaborationtypevalue.length) { // If the collaboration type field isn't selected, reset selection and return.
                selfield.set('selectedIndex', '0')
                return
            }
            deletetitle = M.str.gradingform_erubric.deleteactivity // Set tittle for delete button.
        }

        if (selfield.hasClass('resource'))
            deletetitle = M.str.gradingform_erubric.deleteresource // Set tittle for delete button.

        if (selfield.hasClass('assignment'))
            deletetitle = M.str.gradingform_erubric.deleteassignment // Set tittle for delete button.

        if (ul.hasClass('hiddenelement')) // If the ul is hidden (if empty), reveal it to display the value to be added.
            ul.removeClass('hiddenelement')

        // Cut of long strings and add title.
        lititle = 'title="'+fdesc+'"';
        fdesc = '<span '+lititle+' class="nameoverflowedit">'+fdesc+' ...'+'</span>';

        var valueChunks = fvalue.split('->')

        // The module type icon to be displayed.
        ficon = M.gradingform_erubriceditor.enrichedconst[name]['modulesicons'][valueChunks[0]] + ' '

        // The delete button to be displayed.
        delbtn = '<div class="delete">'+
                 '<input type="submit" name="'+name+'[criteria]['+chunks[2]+'][deletemodule]" '+
                    'id="'+fid+'-'+valueChunks[0]+'-'+valueChunks[1]+'-deletemodule" '+
                    'value="'+fid+'-'+valueChunks[0]+'-'+valueChunks[1]+'" title="'+deletetitle+'" tabindex="-1"/>'+
                 '</div>';

        ul.appendChild('<li id="'+fid+'-'+valueChunks[0]+'-'+valueChunks[1]+'">' +
        '<input type="hidden" name="' + name + '[criteria]['+chunks[2]+'][coursemodules]['+chunks[3]+'][]" value="' + fvalue + '" /> ' + ficon + fdesc + delbtn+ '</li>');

        // Add event listener for delete button.
        M.gradingform_erubriceditor.addhandlers()
    }
    // Reset the select field.
    selfield.set('selectedIndex','0')
}

// Handle enrichment fields that depend on the selected course module (collaboration - grade - study).
M.gradingform_erubriceditor.changecriteriontype = function (e, confirmed, crvalue) {
    var selfield = e.target,
        ul = selfield.get('parentNode').get('parentNode').one('ul'),
        collaborationfield = selfield.get('parentNode').get('parentNode').one('.collaborationtype'),
        referencefield = selfield.get('parentNode').get('parentNode').one('.referencetype');

    var dialog_options = { // Prepare the dialog options.
        'scope' : this,
        'callbackargs' : [e, true, selfield.get('selectedIndex')], //remember the current selected value
        'callback' : M.gradingform_erubriceditor.changecriteriontype
    };

    // Alert and take action according to current chosen course modules.
    if (ul.hasChildNodes()) {

        var Y = M.gradingform_erubriceditor.Y,
            name = M.gradingform_erubriceditor.name

        var currentSelection = ul.one('li')
        if (currentSelection.get('id').indexOf('resource')>0) selfield.set('selectedIndex', M.gradingform_erubriceditor.enrichedconst[name]['selectresource'])
        if (currentSelection.get('id').indexOf('activity')>0) selfield.set('selectedIndex', M.gradingform_erubriceditor.enrichedconst[name]['selectactivity'])
        if (currentSelection.get('id').indexOf('assignment')>0) selfield.set('selectedIndex', M.gradingform_erubriceditor.enrichedconst[name]['selectassignment'])

        if (confirmed) { // If we had confirmation.
            ul.addClass('hiddenelement')
            ul.empty()
            selfield.set('selectedIndex', crvalue)
            // Change enriched values suffix on levels if needed.
            if (referencefield.get('value').length) M.gradingform_erubriceditor.changereferencetypeselect(referencefield)
        } else{ // Display confirmation dialog box.
            dialog_options['message'] = M.str.gradingform_erubric.confirmchangecriteriontype
            M.util.show_confirm_dialog(e, dialog_options);
        }
    } else if (referencefield.get('value').length) { // Change enriched values suffix on levels if needed.
        M.gradingform_erubriceditor.changereferencetypeselect(referencefield)
    }

    // Show or hide the collaboration type select field according to criterion type selection.
    M.gradingform_erubriceditor.checkcollaborationtype(collaborationfield)
}

// Handle enriched value suffix according to reference type field selection, criterion type and collaboration type.
M.gradingform_erubriceditor.changereferencetypeselect = function (e) {
    var selfield = e.target
    if (!selfield) selfield = e // In case changecriteriontype function above, triggered this function.
    var chunks = selfield.get('id').split('-')
    var Y = M.gradingform_erubriceditor.Y,
        name = M.gradingform_erubriceditor.name,
        typefield = Y.one('#'+name+'-criteria-'+chunks[2]+'-criteriontype')

    //In case this function was triggered by a change in the collaboration type field
    if (chunks[3]=='collaborationtype') {
        selfield = Y.one('#'+name+'-criteria-'+chunks[2]+'-referencetype')
    }
    var value = String(selfield.get('value'))

    // If user just started completing the enrichment and this was triggered by a change in the collaboration type field, do nothing.
    if (chunks[3]=='collaborationtype' && !value) return;

    var criteriontypevalue = String(typefield.get('value'))
    var enrichedvaluesuffixfields = Y.all('#'+name+'-enriched-criteria-'+chunks[2]+'-levels i')

    switch(value) {
        case String(M.gradingform_erubriceditor.enrichedconst[name]['referenceabsolutenumber']):
            if (criteriontypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['selectactivity'])) {
                var collaborationtypevalue = String(selfield.get('parentNode').get('parentNode').one('.collaborationtype').get('value'))
                if (collaborationtypevalue.length && collaborationtypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['collaborationpeople'])) {
                    enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixstudents)} )
                }else if (collaborationtypevalue.length && collaborationtypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['collaborationfiles'])) {
                    enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixfiles)} )
                }else{
                    enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixtimes)} )
                }
            }else if (criteriontypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['selectresource'])) {
                enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixtimes)} )
            }else if (criteriontypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['selectassignment'])) {
                enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixpoints)} )
            }else {
                enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixnothing)} )
            }
            break;
        case String(M.gradingform_erubriceditor.enrichedconst[name]['referencepercentage']):
            enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixpercent)} )
            break;
        default:
            enrichedvaluesuffixfields.each( function(node) {M.gradingform_erubriceditor.changeenrichedvaluesuffix(node, M.str.gradingform_erubric.enrichedvaluesuffixnothing)} )
    }
}

// Function to display or hide the collaboration type select field, according to criterion type selected.
M.gradingform_erubriceditor.checkcollaborationtype = function (e) {
    var selfield = e
        selfield = selfield.get('parentNode')
    var name = M.gradingform_erubriceditor.name
    var criteriontypevalue = String(selfield.get('parentNode').one('.criteriontype').get('value'))

    if (criteriontypevalue.length && criteriontypevalue==String(M.gradingform_erubriceditor.enrichedconst[name]['selectactivity'])) {
        if (selfield.hasClass('hiddenelement')) selfield.removeClass('hiddenelement')
    }else{
        // Reset the field value if exists.
        if (e.get('selectedIndex')) {
            e.set('selectedIndex', '0')
            var selplain = e.get('parentNode').one('.plainvaluerich')
            selplain.addClass('empty')
            selplain.one('.textvalue').set('innerHTML', M.str.gradingform_erubric.collaborationempty)
        }
        if (!selfield.hasClass('hiddenelement')) selfield.addClass('hiddenelement')
    }
}

// Change the suffix in the criterion levels enriched values.
M.gradingform_erubriceditor.changeenrichedvaluesuffix = function (el, str) {
    el.empty()
    el.set('innerHTML', str)
}