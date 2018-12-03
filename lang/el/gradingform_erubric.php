<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Learning Analytics Enriched Rubric (e-rubric) - Language File EL (Greek)
 *
 * This file contains all the language strings, which are used in this plugin.
 *
 * @package    gradingform_erubric
 * @category   grading
 * @copyright  2012 John Dimopoulos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// LA e-rubric definitions.
$string['pluginname'] = 'Εμπλουτισμένη Ρουμπρίκα Ανάλυσης Μαθησιακών Αλληλεπιδράσεων';
$string['defineenrichedrubric'] = 'Ορισμός LA e-Rubric';
$string['erubric'] = 'Εμπλουτισμένη Ρουμπρίκα Ανάλυσης Μαθησιακών Αλληλεπιδράσεων';
$string['gradingof'] = '{$a} βαθμολόγηση';
$string['previewerubric'] = 'Προεπισκόπηση LA e-Rubric';
$string['criterion'] = 'Κριτήριο {$a}';
$string['level'] = 'Επίπεδο {$a->definition}, {$a->score} points.';
$string['leveldefinition'] = 'Περιγραφή επιπέδου {$a}';
$string['levelsgroup'] = 'Ομάδα επιπέδων';
$string['scoreinputforlevel'] = 'Απόδοση βαθμού για το επίπεδο {$a}';

// LA e-rubric form fields and buttons.
$string['addcriterion'] = 'Κριτήριο';
$string['backtoediting'] = 'Επιστροφή';
$string['criterionaddlevel'] = 'Επίπεδο';
$string['criteriondelete'] = 'Διαγραφή κριτηρίου';
$string['criterionmovedown'] = 'Μετακίνηση κάτω';
$string['criterionmoveup'] = 'Μετακίνηση πάνω';
$string['deleteactivity'] = 'Διαγραφή δραστηριότητας';
$string['deleteresource'] = 'Διαγραφή πόρου';
$string['deleteassignment'] = 'Διαγραφή εργασίας';
$string['leveldelete'] = 'Διαγραφή επιπέδου';
$string['save'] = 'Αποθήκευση';
$string['saverubric'] = 'Αποθήκευση και ενεργοποίηση ε-ρουμπρίκας';
$string['saverubricdraft'] = 'Πρόχειρη αποθήκευση';
$string['criterionduplicate'] = 'Αντιγραφή κριτηρίου';

// LA e-rubric form fields prefix labels.
$string['participationin'] = 'Έλεγξε:';
$string['collaborationtype'] = 'Τύπου:';
$string['coursemoduleis'] = 'Σε:';
$string['participationis'] = 'Είναι:';
$string['participationon'] = 'Σχετικά με:';
$string['description'] = 'Περιγραφή';
$string['name'] = 'Όνομα';
$string['rubricstatus'] = 'Κατάσταση ε-ρουμπρίκας';

// LA e-rubric pre-defined select fields values description.
$string['selectstudy'] = 'μελέτη';
$string['selectcollaboration'] = 'συνεργασία';
$string['selectgrade'] = 'βαθμολογία';
$string['criterionoperatorequals'] = 'ίσο (=)';
$string['criterionoperatormorethan'] = 'περισσότερο (>=)';
$string['referencetypenumber'] = 'μαθητή';
$string['referencetypepercentage'] = 'σύνολο';
$string['collaborationtypeentries'] = 'συνομιλίες';
$string['collaborationtypefileadds'] = 'υποβολές αρχείων';
$string['collaborationtypereplies'] = 'απαντήσεις σχόλιων';
$string['collaborationtypeinteractions'] = 'πλήθος ατόμων';
$string['addnew'] = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Προσθ. (+)'; // Added spaces in order to move string to the middle of the option select field.

// LA e-rubric confirmation dialogs.
$string['confirmdeletecriterion'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτό το κριτήριο;';
$string['confirmdeletelevel'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτό το επίπεδο;';
$string['confirmdeleteactivity'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτή τη δραστηριότητα;';
$string['confirmdeleteresource'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτόν τον πόρο;';
$string['confirmdeleteassignment'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτή την εργασία;';
$string['confirmchangecriteriontype'] = 'Είστε σίγουροι πως θέλετε να διαγράψετε αυτό το είδος κριτηρίου; Οι υπάρχοντες μαθησιακοί πόροι θα ακυρωθούν.';

// LA e-rubric level enrichment value suffixes and score postfix.
$string['enrichedvaluesuffixpoints'] = 'βαθμοί/100';
$string['enrichedvaluesuffixtimes'] = 'φορές';
$string['enrichedvaluesuffixfiles'] = 'αρχεία';
$string['enrichedvaluesuffixpercent'] = 'επι %';
$string['enrichedvaluesuffixstudents'] = 'άτομα';
$string['enrichedvaluesuffixnothing'] = '<font color="red"><b>!!!</b></font>';
$string['scorepostfix'] = '{$a}βαθμοί';

// LA e-rubric empty form fields description.
$string['criterionempty'] = 'Προσθέστε κριτήριο';
$string['levelempty'] = 'Προσθέστε επίπεδο';
$string['intercactionempty'] = 'Είδος έλεγχου';
$string['coursemoduleempty'] = 'Προσθήκη πόρου';
$string['operatorempty'] = 'Επιλ. τελεστή';
$string['referencetypeempty'] = 'Ατομ. / Συνολ.';
$string['enrichedvalueempty'] = 'Τιμή';
$string['collaborationempty'] = 'Είδος αλληλ/δρασης';

// LA e-rubric info explained.
$string['rubricmapping'] = 'Κανόνες απόδοσης τελικής βαθμολογίας';
$string['rubricmappingexplained'] = 'Το ελάχιστο δυνατό αποτέλεσμα της εμπλουτισμένης ρουμπρίκας είναι <b>{$a->minscore} βαθμοί</b>
    και θα μετατραπεί στο ελάχιστο δυνατό αποτέλεσμα του πόρου αξιολόγησης (αυτής της εργασίας βαθμολόγησης) (που είναι το μηδέν εκτός και αν χρησιμοποιείται κάποια κλίμακα).
    Το μέγιστο αποτέλεσμα <b>{$a->maxscore} βαθμοί</b> θα μετατραπεί στη μέγιστη δυνατή βαθμολόγηση.<br />
    Ενδιάμεσα αποτελέσματα θα μετατραπούν ανάλογα και θα στρογγυλοποιηθούν στον κοντινότερο διαθέσιμο βαθμό.<br />
    Αν χρησιμοποιείται διαβαθμισμένη κλίμακα αντί βαθμολογίας, το τελικό αποτέλεσμα θα αναχθεί στα αντίστοιχα κλιμάκια.<br /><br />
    Μπορείτε να αλλάξετε αυτόν τον τρόπο υπολογισμού, επιλέγοντας \'Υπολογισμός βαθμολογίας θεωρώντας πως η βαθμολογία της ρουμπρίκας ξεκινά από το μηδέν (0)\' στις επιλογές της ρουμπρίκας.';
$string['enrichedrubricinfo'] = 'Κανόνες εμπλουτισμού κριτηρίων';
$string['enrichedrubricinfoexplained'] = 'Τα εμπλουτισμένα κριτήρια θα αξιολογηθούν αυτόματα από το σύστημα, όπως και η επιλογή του κατάλληλου επιπέδου.
                                          Όταν συμβεί αυτό, ο αξιολογητής δεν θα μπορεί να αλλάξει το αποτέλεσμα.<br />
                                          Σε περίπτωση λογικού λάθους των κριτηρίων εμπλουτισμού, δεν θα επιλέγεται αυτόματα επίπεδο,
                                          οπότε οι βαθμοί του συγκεκριμένου κριτηρίου δεν θα προσμετρηθούν και μόνο αν έχει ενεργοποιηθεί η παράκαμψη
                                          ο αξιολογητής θα μπορεί να επιλέξει μόνος του επίπεδο.<br />';
$string['enrichshareconfirm'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>
    Το πρόσθετο <b>Εμπλουτισμένη Ρουμπρίκα Ανάλυσης Μαθησιακών Αλληλεπιδράσεων</b> μπορεί να χρησιμοποιηθεί ως πρότυπο διαμοιρασμού, ΜΟΝΟ ΓΙΑ ΤΟ ΠΑΡΟΝ ΜΑΘΗΜΑ!
    Αν άλλοι χρήστες της πλατφόρμας χρησιμοποιήσουν αυτό το στιγμιότυπο, <b>δεν θα δουλέψει ως έχει</b>! Η βασική δομή της ρουμπρίκας δεν θα αλλοιωθεί,
    όμως θα πρέπει να αντικαταστήσετε τους υπάρχοντες ενσωματωμένους μαθησιακούς πόρους με παρόμοιους από το μάθημα προορισμού.';
$string['criterionremark'] = 'Σχόλιο κριτηρίου {$a->description}: {$a->remark}';
$string['privacy:metadata:fillings'] = 'Ό πίνακας στη Βάση Δεδομένων που αποθηκεύει βαθμολογικές πληροφορίες και παραγόμενα από Learning Analytics και Data Mining.';
$string['privacy:metadata:fillings:instanceid'] = 'Ο κωδικός του στιγμιότυπου βαθμολόγησης ενός βαθμολογητή που χρησιμοποιεί μια ε-ρουμπρίκα.';
$string['privacy:metadata:fillings:criterionid'] = 'Ο κωδικός κριτηρίου μιας ε-ρουμπρίκας που χρησιμοποπιήθηκε για βαθμολόγηση.';
$string['privacy:metadata:fillings:levelid'] = 'Ο κωδικός του επιλεγμένου επιπέδου ενός κριτηρίου ε-ρουμπρίκας.';
$string['privacy:metadata:fillings:remark'] = 'Οι παρατηρήσεις αξιολόγησης του βαθμολογητή, βάση του κριτηρίου της ε-ρουμπρίκας.';
$string['privacy:metadata:fillings:enrichedbenchmark'] = 'Η τελική τιμή αναφοράς που προέκυψε από την επεξεργασία των Μαθησιακών Αλληλεπιδράσεων και την Εξόρυξη Δεδομένων.';
$string['privacy:metadata:fillings:enrichedbenchmarkstudent'] = 'Η τιμή αναφοράς του μαθητή που προέκυψε από την επεξεργασία των Μαθησιακών Αλληλεπιδράσεων και την Εξόρυξη Δεδομένων.';
$string['privacy:metadata:fillings:enrichedbenchmarkstudents'] = 'Η συγκεντρωτική τιμή αναφοράς των εν ενεργεία μαθητών που προέκυψε από την επεξεργασία των Μαθησιακών Αλληλεπιδράσεων και την Εξόρυξη Δεδομένων.';

// LA e-rubric enrichment help icon.
$string['enrichment'] = 'Εμπλουτισμός';
$string['enrichment_help'] = 'Παρακολουθήστε την ταινία παραδείγματος χρήσης για να δημιουργήσετε κριτήρια σε μια Εμπλουτισμένη Ρουμπρίκα Ανάλυσης Μαθησιακών Αλληλεπιδράσεων:
    <br /><br />
    <a target="_blank" href="https://www.youtube.com/watch?v=jCuNm463yTU&hd=1">Δημιουργία στιγμιότυπου Εμπλουτισμένης Ρουμπρίκας Ανάλυσης Μαθησιακών Αλληλεπιδράσεων.</a><br /><br />';

// LA e-rubric description of form errors and alerts.
$string['err_missinglogstores'] = '<p><font color="red"><b>Λάθος Συστήματος Καταγραφής Συμβάντων!</b></font></p>
    Το εργαλείο αυτό μπορεί να λειτουργήσει μόνο αν το <b>Σταθερό</b> είτε το <b>Παραδοσιακό</b> σύστημα καταγραφής συμβάντων έχουν ενεργοποιηθεί.
    Φαίνεται πως κανένα από τα δύο δεν έχει. Απευθυνθείτε στο Διαχειριστή του συστήματος για την ενεργοποίηση των απαραίτητων συστατικών,
    προκειμένου να χρησιμοποιήσετε αυτό το εργαλείο. Για περισσότερες πληροφορίες δείτε τις <a target="_blank" href="https://docs.moodle.org/35/el/Learning_Analytics_Enriched_Rubric">οδηγίες χρήσης του εργαλείου</a>.';
$string['err_criteriontypeid'] = 'Πρέπει να επιλέξετε έναν ή περισσότερους εκπαιδευτικούς Πόρους ή Δραστηριότητες.';
$string['err_criteriontypeid'] = 'Πρέπει να επιλέξετε τελεστή για το εμπλουτισμένο κριτήριο.';
$string['err_criterionmethod'] = 'Πρέπει να επιλέξετε ατομική ή συλλογική αναφορά για το εμπλουτισμένο κριτήριο.';
$string['collaborationochoice'] = 'Πρέπει να επιλέξετε είδος αλληλεπίδρασης πριν προσθέσετε εκπαιδευτικούς πόρους!';
$string['err_enrichedvalueformat'] = 'Η τιμή ελέγχου των επιπέδων εμπλουτισμού πρέπει να είναι έγκυρος θετικός ακέραιος αριθμός.';
$string['err_enrichedvaluemissing'] = 'Τα εμπλουτισμένα κριτήρια πρέπει να έχουν τιμές ελέγχου σε κάθε επίπεδο.';
$string['err_enrichedcriterionmissing'] = 'Πρέπει να επιλεχθούν όλες οι παράμετροι εμπλουτισμού ή καμία.';
$string['err_enrichedmoduleselection'] = 'Οι επιλεγμένοι εκπαιδευτικοί πόροι πρέπει να είναι του ίδιου τύπου, σύμφωνα με το είδος ελέγχου.';
$string['err_collaborationhoice'] = 'Οι εκπαιδευτικοί πόροι άμεσης ομιλίας (chat modules) δεν δύναται να επιλεγούν για έλεγχο απαντήσεων και υποβολής αρχείων σε ομαδικές συζητήσεις (forums).';
$string['err_collaborationtypeneedless'] = 'Το πεδίο "είδος αλληλ/δρασης" πρέπει να επιλέγεται μόνο για ελέγχους συνεργασίας.';
$string['err_missingcoursemodule'] = 'Ελλιπής πόρος!';
$string['err_attention'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>';
$string['err_missingcoursemodules'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>
    Τουλάχιστον ένας εκπαιδευτικός πόρος απουσιάζει από τα κριτήρια εμπλουτισμού!
    Ο πόρος ενδέχεται να έχει διαγραφεί ή αυτό το στιγμιότυπο της ρουμπρίκας να έχει εισαχθεί από άλλο μάθημα.
    Επεξεργαστείτε το τρέχον στιγμιότυπο προκειμένου να εμπλουτίσετε (ή όχι) το(α) εν λόγο κριτήριο(α). Διαφορετικά <b>η αξιολόγηση των μαθητών ενδέχεται να μην είναι εφικτή</b>!';
$string['err_missingcoursemodulesedit'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>
    Τουλάχιστον ένας εκπαιδευτικός πόρος απουσιάζει από τα κριτήρια εμπλουτισμού!
    Μπορείτε να διαγράψετε το εν λόγο κριτήριο ή να το απλουστεύσετε επαναφέροντας τις επιλογές εμπλουτισμού ή να το εμπλουτίσετε με νέες επιλογές.
    <b>Αν δεν ενημερώσετε το παρόν στιγμιότυπο και το αφήσετε ως έχει, ενδέχεται να μην είναι εφικτή η αξιολόγηση των μαθητών!</b>';
$string['err_mintwolevels'] = 'Κάθε κριτήριο πρέπει να έχει τουλάχιστον δυο επίπεδα.';
$string['err_nocriteria'] = 'Η ρουμπρίκα πρέπει να περιέχει τουλάχιστον ένα κριτήριο.';
$string['err_nodefinition'] = 'Ο χαρακτηρισμός των επιπέδων δεν μπορεί να είναι κενός.';
$string['err_nodescription'] = 'Η περιγραφή του κριτηρίου δεν μπορεί να είναι κενή.';
$string['err_scoreformat'] = 'Η τιμή βαθμολόγησης των επιπέδων πρέπει να είναι έγκυρος αριθμός (θετικός ή αρνητικός).';
$string['err_totalscore'] = 'Η μέγιστη δυνατή βαθμολογία πρέπει να είναι μεγαλύτερη του μηδενός.';
$string['zerolevelsabsent'] = 'Προσοχή: Η ελάχιστη δυνατή βαθμολογία για τη συγκεκριμένη ρουμπρίκα δεν είναι μηδέν (0). Αυτό μπορεί να παράγει απροσδόκητα αποτελέσματα για τη συγκεκριμένη βαθμολογική δραστηριότητα.
    Για να αποφύγετε αυτό, κάθε κριτήριο θα πρέπει να έχει ένα επίπεδο με μηδέν (0) βαθμούς.<br>
    Αυτή η προειδοποίηση μπορεί να αγνοηθεί, αν για τη συγκεκριμένη βαθμολογική δραστηριότητα χρησιμοποιείτε διαβαθμισμένη κλίμακα αντί αριθμητικής βαθμολογίας και η ελάχιστη βαθμολογία της ρουμπρίκας αναλογεί στην ελάχιστη τιμή της κλίμακας.';
$string['err_novariations'] = 'Η ελάχιστη βαθμολογία της ρουμπρίκας δεν μπορεί να είναι ίδια με τη μέγιστη.';
$string['err_novariationspoints'] = 'Τα επίπεδα ενός κριτηρίου δεν μπορούν να έχουν τις ίδιες τιμές.';
$string['err_novariationsvalues'] = 'Τα εμπλουτισμένα κριτήρια πρέπει να έχουν διαφορετικές τιμές ελέγχου σε κάθε επίπεδο.';
$string['needregrademessage'] = 'Το στιγμιότυπο της εμπλουτισμένης ρουμπρίκας έχει αλλάξει μετά την αξιολόγηση του συγκεκριμένου μαθητή.
    Ο μαθητής δεν θα μπορεί να δει αυτή τη φόρμα αξιολόγησης μέχρι να ενημερώσετε εκ νέου το βαθμό.';
$string['regrademessage1'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>
    Θέλετε να αποθηκεύσετε νέες αλλαγές σε μια εμπλουτισμένη ρουμπρίκα που έχει ήδη χρησιμοποιηθεί για βαθμολόγηση.
    Ελέγξτε αν οι υπάρχουσες βαθμολογίες πρέπει να επανεξεταστούν.
    Αν το επιλέξετε, η εμπλουτισμένη ρουμπρίκα δεν θα είναι ορατή από τους μαθητές μέχρι να βαθμολογηθούν εκ νέου.';
$string['regrademessage2'] = '<p style="text-align:center;color:red;font-weight:bold">ΠΡΟΣΟΧΗ!</p>
    Θέλετε να αποθηκεύσετε νέες αλλαγές σε μια εμπλουτισμένη ρουμπρίκα που έχει ήδη χρησιμοποιηθεί για βαθμολόγηση.
    Η υπάρχουσα βαθμολογία δεν θα αλλάξει, αλλά η εμπλουτισμένη ρουμπρίκα δεν θα είναι ορατή από τους μαθητές μέχρι να βαθμολογηθούν εκ νέου.';
$string['regradeoption0'] = 'Αποτροπή επανέλεγχου';
$string['regradeoption1'] = 'Σήμανση για επανέλεγχο';
$string['restoredfromdraft'] = 'Σημείωση: Η τελευταία απόπειρα βαθμολόγησης αυτού του ατόμου δεν αποθηκεύτηκε, οπότε η πρότερη βαθμολογία επανακτήθηκε.
    Αν θέλετε να ακυρώσετε αυτές τις αλλαγές πατήστε το κουμπί \'Άκυρο\' παρακάτω.';
$string['rubricnotcompleted'] = 'Πρέπει να επιλεχθεί ένα κατάλληλο επίπεδο για κάθε κριτήριο.';

// LA e-rubric evaluation results.
$string['benchmarkinfo'] = 'Αποτελέσματα ανάλυσης μαθησιακών αλληλεπιδράσεων';
$string['benchmarkfinal'] = 'Τελική τιμή αναφοράς μαθητή';
$string['studentbenchmarkinfo'] = 'Τιμή αναφοράς μαθητή';
$string['studentsbenchmarkinfo'] = 'Τιμή αναφοράς μαθητών';
$string['benchmarkinfonull'] = 'Δεν υπάρχουν αποτελέσματα από την ανάλυση των μαθησιακών αλληλεπιδράσεων';

// LA e-rubric simple rubric options.
$string['rubricoptions'] = 'Επιλογές ρουμπρίκας';
$string['sortlevelsasc'] = 'Ταξινόμηση επιπέδων:';
$string['sortlevelsasc0'] = 'Φθίνουσα με βάση τη βαθμολογία';
$string['sortlevelsasc1'] = 'Αύξουσα με βάση τη βαθμολογία';
$string['alwaysshowdefinition'] = 'Να επιτρέπεται η προεπισκόπηση της ρουμπρίκας από τους μαθητές<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    (αλλιώς η ρουμπρίκα θα είναι ορατή μόνο μετά τη βαθμολόγηση)';
$string['showdescriptionstudent'] = 'Εμφάνιση περιγραφής στιγμιότυπου στους βαθμολογούμενους';
$string['showdescriptionteacher'] = 'Εμφάνιση περιγραφής στιγμιότυπου κατά τη βαθμολόγηση';
$string['showscorestudent'] = 'Εμφάνιση βαθμολογίας επιπέδων στους βαθμολογούμενους';
$string['showscoreteacher'] = 'Εμφάνιση βαθμολογίας επιπέδων κατά τη βαθμολόγηση';
$string['enableremarks'] = 'Ενεργοποίηση σχόλιων ανά κριτήριο κατά τη βαθμολόγηση';
$string['showremarksstudent'] = 'Εμφάνιση σχόλιων στους βαθμολογούμενους';
$string['lockzeropoints'] = 'Υπολογισμός βαθμολογίας θεωρώντας πως η βαθμολογία της ρουμπρίκας ξεκινά από το μηδέν (0).';
$string['lockzeropoints_help'] = 'Αυτή η παράμετρος εφαρμόζεται αν το άθροισμα των ελάχιστων βαθμών όλων των κριτηρίων
    είναι μεγαλύτερο του μηδενός (0). Αν ενεργοποιηθεί, η ελάχιστη δυνατή βαθμολογία όλης της ρουμπρίκας
    θα είναι μεγαλύτερη του μηδενός (0). Αν απενεργοποιηθεί, η ελάχιστη δυνατή βαθμολογία όλης της ρουμπρίκας
    θα αναχθεί στην ελάχιστη βαθμολογία της βαθμολογικής δραστηριότητας που ανήκει,
    που θα είναι μηδέν (0), εκτός και αν χρησιμοποιείται διαβαθμισμένη κλίμακα.
    Για περισσότερες πληροφορίες δείτε τις οδηγίες χρήσης του εργαλείου σχετικά με τον <a target="_blank" href="https://docs.moodle.org/35/el/Learning_Analytics_Enriched_Rubric#Grade_calculation">υπολογισμό βαθμολογίας</a>';

// LA e-rubric enrichment options.
$string['enrichmentoptions'] = 'Επιλογές εμπλουτισμού κριτηρίων';
$string['showenrichedvaluestudent'] = 'Εμφάνιση τιμών ελέγχου των επιπέδων στους βαθμολογούμενους';
$string['showenrichedvalueteacher'] = 'Εμφάνιση τιμών ελέγχου των επιπέδων κατά τη βαθμολόγηση';
$string['showenrichedcriteriastudent'] = 'Εμφάνιση εμπλουτισμού των κριτηρίων στους βαθμολογούμενους';
$string['showenrichedcriteriateacher'] = 'Εμφάνιση εμπλουτισμού των κριτηρίων κατά τη βαθμολόγηση';
$string['timestampenrichmentend'] = 'Οι έλεγχοι εμπλουτισμού γίνονται μέχρι την ημερομηνία υποβολής (αν έχει οριστεί)';
$string['timestampenrichmentstart'] = 'Οι έλεγχοι εμπλουτισμού γίνονται από την ημερομηνία ενεργοποίησης της εργασίας (αν έχει οριστεί)';
$string['overideenrichmentevaluation'] = 'Παράκαμψη της αυτόματης διαδικασίας αξιολόγησης σε περίπτωση λογικού λάθους εμπλουτισμού<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                          (<i>Αν υπάρχουν λογικά λάθη εμπλουτισμού, η αξιολόγηση δεν θα είναι αλλιώς εφικτή!)</i>';
$string['showenrichedbenchmarkteacher'] = 'Εμφάνιση των παραγόμενων τιμών αναφοράς κατά τη βαθμολόγηση';
$string['showenrichedbenchmarkstudent'] = 'Εμφάνιση των παραγόμενων τιμών αναφοράς στους βαθμολογούμενους';