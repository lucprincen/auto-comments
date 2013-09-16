var _post_id = wpc.post_id;
var _time = wpc.time;


jQuery(document).ready( function($) {
 

    console.log( 'initial time:'+ _time );

    //set the tempo of a heartbeat:
    wp.heartbeat.interval( 'fast' );


    //send the post_id + timestamp on every beat, to check for new comments:
    $(document).on( 'heartbeat-send.wpc_comment_update', function( event, data ){

        data['wpc_comment_update'] = { 'post_id': _post_id, 'timestamp': _time }

    });


    //On a tick, check for a response from the server:
    $(document).on( 'heartbeat-tick.wpc_comment_update', function( event, data ) {

        //we have a response!
        if ( data.hasOwnProperty( 'wpc_comment_update' ) ) {

            var response = data['wpc_comment_update'];

            //log the response:
            console.log( response );

            //update the _time reference in the heartbeat:
            _time = response.timestamp;

            //add the html to the comment-list:
            $('.comment-list').append( response.html );
          

        }

    
    });

});
 