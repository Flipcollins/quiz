<?php
function print2Smallest($arr)
{
    $INT_MAX = 10;
    $arr_size = count($arr);
    $_1n2small = [];

    $first = $second = $INT_MAX;
    for ($i = 0; $i < $arr_size ; $i ++)
    {
        if ($arr[$i] < $first)
        {
            $second = $first;
            $first = $arr[$i];
        }

        else if ($arr[$i] < $second &&
            $arr[$i] != $first)
            $second = $arr[$i];
    }
    if ($second == $INT_MAX){
        array_push($_1n2small, $first);
    } else{
        array_push($_1n2small, $first, $second);
    }
    return $_1n2small;
}



add_action( 'gform_pre_submission_1', 'pre_submission_handler', 10, 2 );

function pre_submission_handler($form ) {

    $questions = [];
    $groups = [];
    $inputs = [];
    $weakAreas = [];
    $dups = [];
    $duplicateQuestions = [];

    foreach($form['fields'] as $k => $field):
        if($field["type"]=='radio'):
            $groups[$k]['question'] = $field['label'];
            $groups[$k]['value'] = $field['description'];
            $questions[$k]['question'] = $field['label'];
            $questions[$k]['answers'] = $field['choices'];
        endif;
    endforeach;

    array_multisort($groups , SORT_NUMERIC);

    foreach($_POST as $ki => $post):
        if((int) $post != 0):
            $check = preg_match('/(input_[0-9]+)/', $ki, $matches, PREG_OFFSET_CAPTURE);
            if($check == 1):
                array_push($inputs, $post);
            endif;
        endif;
    endforeach;

    array_multisort($questions , SORT_NUMERIC);

    $firtNsecondSmallest = print2Smallest($inputs);

    foreach($questions as $kii => &$question):
        foreach($question['answers'] as &$answer):
            if($answer['value'] == $inputs[$kii]):
                $answer["isSelected"] = true;
                $answer["group"] = $groups[$kii]['value'];
            endif;
            if(in_array($answer['value'],$firtNsecondSmallest) && $answer["isSelected"]):
                array_push($weakAreas, [
                        'question' => $question['question'],
                        'value' => $answer['value'],
                        'group' => $answer['group']
                    ]
                );
            endif;
        endforeach;
    endforeach;

    for($i =0; $i < count($weakAreas); $i++):
        $current = $weakAreas[$i]['value'];
        if(!$dups){
            array_push($dups, $current);
            array_push($duplicateQuestions,$weakAreas[$i]);
        }elseif(in_array($current, $dups)){
            array_push($dups, $current);
            array_push($duplicateQuestions,$weakAreas[$i]);
        }
    endfor;

    $_COOKIE['weakAreas'] = $weakAreas;
    $_COOKIE['duplicateQuestions'] = $duplicateQuestions;
    $_SESSION['duplicateQuestions'] = $duplicateQuestions;


}

add_filter( 'gform_confirmation_1', 'custom_confirmation', 10, 4 );

function custom_confirmation($confirmation, $form, $entry, $ajax){

    $rerate = base64_encode(serialize($_COOKIE['duplicateQuestions']));
    if(count($rerate) > 0):
        $confirmation = array( 'redirect' => 'http://localhost:8001/wordpress/re-ranking?rerate='.$rerate );
    endif;

    return $confirmation;
}



add_filter( 'gform_pre_render_3', 'populate_rereate' );
add_filter( 'gform_pre_validation_3', 'populate_rereate' );
add_filter( 'gform_pre_submission_filter_3', 'populate_rereate' );
add_filter( 'gform_admin_pre_render_3', 'populate_rereate' );
function populate_rereate( $form ) {
    $choices = [
        ['value' => 1, 'text' => 'One', 'isSelected' => false],
        ['value' => 2, 'text' => 'Two', 'isSelected' => false],
        ['value' => 3, 'text' => 'Three', 'isSelected' => false],
        ['value' => 4, 'text' => 'Four', 'isSelected' => false],
        ['value' => 5, 'text' => 'Five', 'isSelected' => false],
        ['value' => 6, 'text' => 'Six', 'isSelected' => false],
        ['value' => 7, 'text' => 'Seven', 'isSelected' => false],
        ['value' => 8, 'text' => 'Eight', 'isSelected' => false],
        ['value' => 9, 'text' => 'Nine', 'isSelected' => false],
        ['value' => 10, 'text' => 'Ten', 'isSelected' => false]
    ];
    $reratesPassed = unserialize(base64_decode($_REQUEST['rerate']));
    //var_dump($txt);die;

    foreach($reratesPassed as $key => $rerate){
        $key++;
        $properties['type'] = 'radio';
        $properties['id'] = $key;
        $properties['label'] = $rerate['question'];
        $properties['adminLabel'] = '';
        $properties['inputs'] = '';
        $properties['isRequired'] = false;
        $properties['formId'] = 1;
        $properties['choices'] = $choices;
        $field = GF_Fields::create( $properties );
        $form['fields'][] = $field;

    }
    GFAPI::update_form( $form );

    return $form;
}

add_filter( 'gform_pre_render_2', 'populate_posts' );
add_filter( 'gform_pre_validation_2', 'populate_posts' );
add_filter( 'gform_pre_submission_filter_2', 'populate_posts' );
add_filter( 'gform_admin_pre_render_2', 'populate_posts' );
function populate_posts( $form ) {

    $taxonomies = array(
        'groups',
    );

    $taxArgs = array(
        'orderby'           => 'name',
        'order'             => 'ASC',
        'hide_empty'        => true
    );

    $terms = get_terms( $taxonomies, $taxArgs );

    foreach ( $terms as $term ) {
        $terms_ids[] = $term->slug;
    }

    $args = array(
        'showposts' => -1,
        'post_type' => 'questions',
        'order' => 'ASC',
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'groups',
                'field'    => 'slug',
                'terms'    => $terms_ids
            )
        ),
    );

    $posts = get_posts($args);
    $choices = [
        ['value' => 1, 'text' => 'One', 'isSelected' => false],
        ['value' => 2, 'text' => 'Two', 'isSelected' => false],
        ['value' => 3, 'text' => 'Three', 'isSelected' => false],
        ['value' => 4, 'text' => 'Four', 'isSelected' => false],
        ['value' => 5, 'text' => 'Five', 'isSelected' => false],
        ['value' => 6, 'text' => 'Six', 'isSelected' => false],
        ['value' => 7, 'text' => 'Seven', 'isSelected' => false],
        ['value' => 8, 'text' => 'Eight', 'isSelected' => false],
        ['value' => 9, 'text' => 'Nine', 'isSelected' => false],
        ['value' => 10, 'text' => 'Ten', 'isSelected' => false]
    ];

    foreach($posts as $key => $post){
        $properties['type'] = 'radio';
        $properties['id'] = $post->ID;
        $properties['label'] = $post->post_title;
        $properties['adminLabel'] = '';
        $properties['inputs'] = '';
        $properties['isRequired'] = false;
        $properties['formId'] = 1;
        $properties['choices'] = $choices;
        $field = GF_Fields::create( $properties );
        $form['fields'][] = $field;

    }

    //GFAPI::update_form( $form );

    return $form;
}
?>

