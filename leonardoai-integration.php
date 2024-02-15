<?php
/**
 * Plugin Name: Leonardo.ai Integration for Wordpress
 * Plugin URI: 
 * Description: This plugin integrates leonardo.ai API into Wordpress for tuvvaldemo.pixagor.net
 * Version: 1.0.0
 */


final class Gbams123_LeonardoAI_Integration {
    public function __construct()
    {
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        add_shortcode('gbams123_leonardoai_image_form', [$this, 'display_form']);

        add_action( 'wp_ajax_gbams123_leonardoai_prompt_form', [$this, 'process_form']);
        add_action( 'wp_ajax_nopriv_gbams123_leonardoai_prompt_form', [$this, 'process_form']);

        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function ttt()
    {
        $generationID = "2beaebfb-9910-4d38-a7a4-7eee61a74333";
        $url = "https://cloud.leonardo.ai/api/rest/v1/generations/$generationID";
        $token = get_option('gbams123-leonardoai-api-token');
        
        $response = wp_remote_get($url, [
            'timeout'   => 30, 
            'sslverify'  => false,
            'headers'   => [
                            'Content-Type'  => 'application/json',
                            'authorization' => 'Bearer '.$token,
                        ],
        ]);

        if(empty($response->errors))    
        {
            $response = json_decode($response['body']);
            // var_dump($response); exit;   
            if(!empty($response->generations_by_pk->generated_images))
            {
                $images = $response->generations_by_pk->generated_images;
                echo json_encode([
                    'status'    => 'success',
                    'images'    => $images
                ]);
                exit;
            }
            else
            {
                $errors[] = 'No Result';
            }
        }
        else
        {
            if(!empty($response['response']['code']))
            {
                $response = json_decode($response['body'], true);
                $errors[] = $response['error'];
            }
            else
            {
                $errors[] = 'An Error Occurred';
            }
        }

        var_dump($errors);
    }
    public function activate()
    {

    }

    public function deactivate()
    {

    }

    public function admin_menu()
    {
        add_submenu_page(
            'options-general.php',
            'LeonardoAI API Integration Settings',
            'LeonardoAI Settings',
            'manage_options',
            'leonardoai-settings',
            [$this, 'show_admin_menu']
        );
    }

    public function show_admin_menu()
    {
        if(!current_user_can('manage_options'))
        {
            wp_die("Access Denied.");
        }
        
        if(!empty($_POST['gbams123-leonardoai-api-token']))
        {   
            update_option('gbams123-leonardoai-api-token', esc_html($_POST['gbams123-leonardoai-api-token']));
        }
        
        $apiKey = get_option('gbams123-leonardoai-api-token');
    
        require('inc/settings-form.php');
    }

    public function process_form()
    {
        if(!empty($_POST['gen_id']))
        {// THE API Might return a status of Pending on the first image generation request. An Ajax call will automatically retry the request after 10 seconds
            return $this->fetchImagesByGenID($_POST['gen_id']);
        }

        $token = get_option('gbams123-leonardoai-api-token');
        if(empty($token))
        {
            $errors[] = "API Token not specified";
        }

        if(empty($_POST['gbams123-leonardoai-form-prompt']))
        {
            $errors[] = "Missing form prompt";
        }

        if(!empty($errors))
        {
            echo json_encode([
                'status'    => 'error',
                'errors'    => $errors
            ]);
            exit;
        }

        $postData = [
            'prompt'    => esc_html($_POST['gbams123-leonardoai-form-prompt'])
        ];

        if(!empty($_POST['gbams123-leonardoai-form-width']) && is_numeric($_POST['gbams123-leonardoai-form-width']))
        {
            // WIDTH MUST BE A MULTIPLE OF 8, SO IF USER ENTERS A FIGURE THAT ISN'T MULTIPLE OF 8, TAKE IT UPWARDS
            $width      = (int) $_POST['gbams123-leonardoai-form-width'];
            $remainder  = $width % 8;
            if($remainder > 0)
            {
                $width += 8-$remainder;
            }

            if($width < 32)
            {
                $width = 32;
            }

            if($width > 1024)
            {
                $width = 1024;
            }
            $postData['width'] = $width;
        }

        if(!empty($_POST['gbams123-leonardoai-form-height']) && is_numeric($_POST['gbams123-leonardoai-form-height']))
        {
            // HEIGHT MUST BE A MULTIPLE OF 8, SO IF USER ENTERS A FIGURE THAT ISN'T MULTIPLE OF 8, TAKE IT UPWARDS
            $height      = (int) $_POST['gbams123-leonardoai-form-height'];
            $remainder  = $height % 8;
            if($remainder > 0)
            {
                $height += 8-$remainder;
            }

            if($height < 32)
            {
                $height = 32;
            }

            if($height > 1024)
            {
                $height = 1024;
            }

            $postData['height'] = $height;
        }

        $response = wp_remote_post("https://cloud.leonardo.ai/api/rest/v1/generations", [
            'timeout'   => 30, 
            'sslverify' => false,
            'body'      => wp_json_encode($postData),
            'headers'   => [
                            'Content-Type'  => 'application/json',
                            'accept'        => 'application/json',
                            'authorization' => 'Bearer '.$token,
                        ],
        ]);

        if(empty($response->errors) && ($response['response']['code'] == 200 || $response['response']['code'] == 201))    
        {
            $response       = json_decode($response['body']);

            $generationID = $response->sdGenerationJob->generationId;

            if(!empty($generationID))
            {
                // IN MANY CASES, THE IMAGES DOES NOT GENERATE INSTANTLY, SO WE WAIT 10 SECONDS BEFORE TRYING TO FETCH GENERATED IMAGES
                sleep(10);
                $url = "https://cloud.leonardo.ai/api/rest/v1/generations/$generationID";

                $response = wp_remote_get($url, [
                    'timeout'   => 30, 
                    'sslverify'  => false,
                    'headers'   => [
                                    'Content-Type'  => 'application/json',
                                    'authorization' => 'Bearer '.$token,
                                ],
                ]);

                if(empty($response->errors))    
                {
                    $response = json_decode($response['body']);
                    
                    if(!empty($response->generations_by_pk->generated_images))
                    {
                        $images = $response->generations_by_pk->generated_images;
                        echo json_encode([
                            'status'        => 'success',
                            'download_url'  => plugin_dir_url( __FILE__ ).'/download.php',
                            'images'        => $images
                        ]);
                        exit;
                    }
                    else if(!empty($response->generations_by_pk->status) && strtoupper($response->generations_by_pk->status) == 'PENDING')
                    {
                        echo json_encode([
                            'status'    => 'pending',
                            'gen_id'    => $generationID,
                        ]);
                        exit;
                    }
                    else
                    {
                        $errors[] = 'No Result';
                    }
                }
                else
                {
                    if(!empty($response['response']['code']))
                    {
                        $response = json_decode($response['body'], true);
                        $errors[] = $response['error'];
                    }
                    else
                    {
                        $errors[] = 'An Error Occurred';
                    }
                }
            }
            else
            {
                $errors[]   = 'Unable to generate Image for Inputted Prompt';
            }
        }
        else
        {
            if(!empty($response['response']['code']))
            {
                $response = json_decode($response['body'], true);
                $errors[] = $response['error'];
            }
            else
            {
                $errors[] = 'An Error Occurred';
            }
        }

        echo json_encode([
            'status'    => 'error',
            'errors'    => $errors
        ]);
        exit;
    }

    public function fetchImagesByGenID($generationID)
    {
        $url = "https://cloud.leonardo.ai/api/rest/v1/generations/$generationID";
        $token = get_option('gbams123-leonardoai-api-token');

        $response = wp_remote_get($url, [
            'timeout'   => 30, 
            'sslverify'  => false,
            'headers'   => [
                            'Content-Type'  => 'application/json',
                            'authorization' => 'Bearer '.$token,
                        ],
        ]);

        if(empty($response->errors))    
        {
            $response = json_decode($response['body']);
            
            if(!empty($response->generations_by_pk->generated_images))
            {
                $images = $response->generations_by_pk->generated_images;
                echo json_encode([
                    'status'    => 'success',
                    'download_url'  => plugin_dir_url( __FILE__ ).'/download.php',
                    'images'    => $images
                ]);
                exit;
            }
            else
            {
                $errors[] = 'No Result';
            }
        }
        else
        {
            if(!empty($response['response']['code']))
            {
                $response = json_decode($response['body'], true);
                $errors[] = $response['error'];
            }
            else
            {
                $errors[] = 'An Error Occurred';
            }
        }


        echo json_encode([
            'status'    => 'error',
            'errors'    => $errors
        ]);
        exit;
    }
    /**
     * THIS DISPLAYS THE FORM WHEREVER THE SHORTCODE IS ADDED.
    */
    public function display_form()
    {
        if(!empty($_POST['gbams123_leonardoai_form']))
        {// PROCESS FORM SUBMISSION
            $this->process_form();
        }

        // DISPLAY FORM
        ob_start();
        require('inc/form.php');
        $content = ob_get_clean();

        return $content;
    }
}

new Gbams123_LeonardoAI_Integration();