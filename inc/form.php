<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script> -->
<style>
/* Style inputs, select elements and textareas */
.gbams123-leonardoai-container input[type=text], .gbams123-leonardoai-container input[type=number]{
    width: 100%;
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    resize: vertical;
}

/* Style the label to display next to the inputs */
.gbams123-leonardoai-container label {
    padding: 12px 12px 12px 0;
    display: inline-block;
}

/* Style the submit button */
.gbams123-leonardoai-container input[type=submit] {
    background-color: #04AA6D;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    float: right;
}

/* Style the gbams123-leonardoai-container */
.gbams123-leonardoai-container {
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
}

/* Floating column for labels: 25% width */
.gbams123-leonardoai-container .col-25 {
    float: left;
    width: 25%;
    margin-top: 6px;
}

/* Floating column for inputs: 75% width */
.gbams123-leonardoai-container .col-75 {
    float: left;
    width: 75%;
    margin-top: 6px;
}

/* Clear floats after the columns */
.gbams123-leonardoai-container .row:after {
    content: "";
    display: table;
    clear: both;
}

.gbams123-leonardoai-images-container {
        display: flex;
        flex-wrap: wrap;
        padding: 0 4px;
    }

/* Create four equal columns that sits next to each other */
.gbams123-leonardoai-images-container .column {
    /* flex: 25%; */
    max-width: 200px;
    max-height: 200px;
    padding: 0 4px;
    text-align: center;
}

.gbams123-leonardoai-images-container .column img {
    margin-top: 8px;
    vertical-align: middle;
    width: 100%;
    /* max-width: 25px; */
}

.gbams123-leonardoai-images-container .column a {
    text-align: center;
    text-decoration: none;
}

@media screen and (max-width: 800px) {
    .gbams123-leonardoai-images-container .column {
        flex: 50%;
        max-width: 50%;
    }
}

@media screen and (max-width: 600px) {
    .gbams123-leonardoai-container .col-25, .gbams123-leonardoai-container .col-75, .gbams123-leonardoai-container input[type=submit] {
        width: 100%;
        margin-top: 0;
    }

    .gbams123-leonardoai-images-container .column {
        flex: 100%;
        max-width: 100%;
    }
}
</style>

<div class="gbams123-leonardoai-container">
    <form action="" id="gbams123-leonardoai-form">
        <input type="hidden" name="action" value="gbams123_leonardoai_prompt_form">
        <div class="row">
            <div class="col-25">
            <label for="gbams123-leonardoai-form-prompt">Prompt <span style="color:red">*</span></label>
            </div>
            <div class="col-75">
                <input type="text" id="gbams123-leonardoai-form-prompt" name="gbams123-leonardoai-form-prompt" placeholder="Enter Prompt" required>
                <span id="gbams123-leonardoai-form-prompt-error" style="color:red"></span>
            </div>
        </div>
        <div class="row">
            <div class="col-25">
                <label for="gbams123-leonardoai-form-width">Width</label>
            </div>
            <div class="col-75">
                <input type="number" step="1" id="gbams123-leonardoai-form-width" name="gbams123-leonardoai-form-width" placeholder="Width" min="32" max="1024">
            </div>
        </div>
        <div class="row">
            <div class="col-25">
                <label for="gbams123-leonardoai-form-height">Height</label>
            </div>
            <div class="col-75">
                <input type="number" step="1" id="gbams123-leonardoai-form-height" name="gbams123-leonardoai-form-height" placeholder="Height" min="32" max="1024">
            </div>
        </div>
        <div class="row">
            <input type="submit" id="gbams123-leonardoai-form-submit-btn" value="Submit">
        </div>
        <div class="row" id="gbams123-leonardoai-error-messages-div" style="color:red">

        </div>
    </form>
    <div class="gbams123-leonardoai-images-container" id="gbams123-leonardoai-images-div">

    </div>
</div>

<script>
    $("#gbams123-leonardoai-form").submit(function(e){
        e.preventDefault();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>', 
            type: "POST",             
            data: $("#gbams123-leonardoai-form").serialize(),
            dataType: 'json',   
            beforeSend: function()
            {
                if($("#gbams123-leonardoai-form-prompt").val() == '')
                {
                    $("gbams123-leonardoai-form-prompt-error").html("Please enter a prompt");
                    return;
                }
                
                $("#gbams123-leonardoai-images-div").html("");
                $("gbams123-leonardoai-form-prompt-error").html("");
                $("#gbams123-leonardoai-error-messages-div").html("");
                $("#gbams123-leonardoai-form-submit-btn").val('Please Wait...');
                $("#gbams123-leonardoai-form-submit-btn").attr('disabled', true);
            },

            success: function(data) {
                if(data.status == 'success')
                {
                    processSuccessResponse(data);
                }
                else if(data.status == 'pending')
                {
                    // IF Pending was returned on the first image generation request. Retry after 10 seconds
                    // Wait 10 seconds
                    return setTimeout(function () {
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>', 
                            type: "POST",             
                            data: {
                                action: 'gbams123_leonardoai_prompt_form',
                                gen_id: data.gen_id,
                            },
                            dataType: 'json',
                            success: function(data) {
                                if(data.status == 'success')
                                {
                                    processSuccessResponse(data);
                                }
                                else
                                {
                                    processErrorResponse(data);
                                }  
                            }
                        });
                    }, 10000);
                }
                else
                {
                    processErrorResponse(data);
                }
            },

            error: function(data){
            },

            complete: function(data)
            {

            }
        });
    });

    function doCompleteAction()
    {
        $("#gbams123-leonardoai-form-submit-btn").val('Submit');
        $("#gbams123-leonardoai-form-submit-btn").attr('disabled', false);
    }

    function processSuccessResponse(data)
    {
        let images  = data.images;
        let du      = data.download_url;

        for (const i in images)
        {
    
            $("#gbams123-leonardoai-images-div").append(`
                <div class="column">
                    <img src="${images[i]['url']}" id="gbams123-leonardoai-image-${images[i]['id']}">
                    <a href="${du}?f=${images[i]['url']}" id="gbams123-leonardoai-image-link-${images[i]['id']}" download>
                        Download
                    </a>
                </div>
            `);    
        }
        doCompleteAction();
    }

    function processErrorResponse(data)
    {
        let errors = data.errors;
        for (const i in errors) {
            $("#gbams123-leonardoai-error-messages-div").append(`
                <p class="text-danger">${errors[i]}</p>
            `);
        }

        $("#gbams123-leonardoai-error-messages-div").focus();
        doCompleteAction();
    }
</script>