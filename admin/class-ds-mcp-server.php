<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * DS Toolkit MCP Server
 *
 * Registers a REST endpoint at /wp-json/ds-toolkit/v1/mcp that speaks the
 * Model Context Protocol (JSON-RPC 2.0). Claude Desktop / Claude Code can
 * connect to this endpoint using a WordPress Application Password for auth,
 * and use the exposed tools to read and edit posts, pages, and DS Toolkit
 * settings — no extra plugins required.
 *
 * Auth: HTTP Basic Authentication with WordPress Application Passwords (WP 5.6+)
 * Capability required: edit_posts (for post tools), manage_options (for settings tools)
 */
class DS_MCP_Server {

    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'ds-toolkit/v1', '/mcp', array(
            'methods'             => array( 'POST', 'GET' ),
            'callback'            => array( $this, 'handle_request' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    public function check_permission( $request ) {
        // GET is used by some MCP clients for capability discovery — allow unauthenticated
        if ( $request->get_method() === 'GET' ) {
            return true;
        }
        return is_user_logged_in() && current_user_can( 'edit_posts' );
    }

    public function handle_request( WP_REST_Request $request ) {
        // GET: return a simple server-info response for discoverability
        if ( $request->get_method() === 'GET' ) {
            return new WP_REST_Response( array(
                'server'  => 'DS Toolkit MCP',
                'version' => DS_TOOLKIT_VERSION,
                'protocol' => '2024-11-05',
                'endpoint' => rest_url( 'ds-toolkit/v1/mcp' ),
            ), 200 );
        }

        $body = $request->get_json_params();

        if ( ! is_array( $body ) || ! isset( $body['jsonrpc'] ) || $body['jsonrpc'] !== '2.0' ) {
            return $this->rpc_error( null, -32600, 'Invalid Request — expected JSON-RPC 2.0' );
        }

        $method = isset( $body['method'] ) ? $body['method'] : '';
        $id     = isset( $body['id'] ) ? $body['id'] : null;
        $params = isset( $body['params'] ) ? $body['params'] : array();

        switch ( $method ) {
            case 'initialize':
                return $this->handle_initialize( $id, $params );
            case 'notifications/initialized':
                // Client notifies server it processed the initialize response — no reply needed
                return new WP_REST_Response( null, 204 );
            case 'tools/list':
                return $this->handle_tools_list( $id );
            case 'tools/call':
                return $this->handle_tools_call( $id, $params );
            case 'ping':
                return $this->rpc_result( $id, new stdClass() );
            default:
                return $this->rpc_error( $id, -32601, 'Method not found: ' . $method );
        }
    }

    // -------------------------------------------------------------------------
    // MCP Methods
    // -------------------------------------------------------------------------

    private function handle_initialize( $id, $params ) {
        return $this->rpc_result( $id, array(
            'protocolVersion' => '2024-11-05',
            'capabilities'    => array(
                'tools' => new stdClass(),
            ),
            'serverInfo' => array(
                'name'    => 'DS Toolkit',
                'version' => DS_TOOLKIT_VERSION,
            ),
            'instructions' => 'DS Toolkit MCP server for WordPress. Use tools to read/edit posts, pages, and DS Toolkit feature settings. All write operations respect WordPress capabilities.',
        ) );
    }

    private function handle_tools_list( $id ) {
        return $this->rpc_result( $id, array(
            'tools' => $this->get_tools_schema(),
        ) );
    }

    private function handle_tools_call( $id, $params ) {
        $name      = isset( $params['name'] ) ? $params['name'] : '';
        $arguments = isset( $params['arguments'] ) ? $params['arguments'] : array();

        switch ( $name ) {
            case 'list_posts':
                return $this->tool_list_posts( $id, $arguments );
            case 'get_post':
                return $this->tool_get_post( $id, $arguments );
            case 'create_post':
                return $this->tool_create_post( $id, $arguments );
            case 'update_post':
                return $this->tool_update_post( $id, $arguments );
            case 'delete_post':
                return $this->tool_delete_post( $id, $arguments );
            case 'get_toolkit_settings':
                return $this->tool_get_toolkit_settings( $id );
            case 'update_toolkit_settings':
                return $this->tool_update_toolkit_settings( $id, $arguments );
            default:
                return $this->rpc_error( $id, -32602, 'Unknown tool: ' . $name );
        }
    }

    // -------------------------------------------------------------------------
    // Tools
    // -------------------------------------------------------------------------

    private function tool_list_posts( $id, $args ) {
        $query_args = array(
            'post_type'      => ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post',
            'posts_per_page' => ! empty( $args['per_page'] ) ? min( (int) $args['per_page'], 100 ) : 20,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }
        if ( ! empty( $args['status'] ) ) {
            $query_args['post_status'] = sanitize_key( $args['status'] );
        }
        $query = new WP_Query( $query_args );
        $posts = array();
        foreach ( $query->posts as $post ) {
            $posts[] = array(
                'id'           => $post->ID,
                'title'        => $post->post_title,
                'status'       => $post->post_status,
                'type'         => $post->post_type,
                'url'          => get_permalink( $post->ID ),
                'date'         => $post->post_date,
                'modified'     => $post->post_modified,
            );
        }
        return $this->tool_result( $id, array(
            'total' => $query->found_posts,
            'posts' => $posts,
        ) );
    }

    private function tool_get_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        return $this->tool_result( $id, array(
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'content'  => $post->post_content,
            'excerpt'  => $post->post_excerpt,
            'status'   => $post->post_status,
            'type'     => $post->post_type,
            'url'      => get_permalink( $post->ID ),
            'date'     => $post->post_date,
            'modified' => $post->post_modified,
            'author'   => get_the_author_meta( 'display_name', $post->post_author ),
        ) );
    }

    private function tool_create_post( $id, $args ) {
        if ( ! current_user_can( 'publish_posts' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — publish_posts required' );
        }
        $post_data = array(
            'post_title'   => sanitize_text_field( isset( $args['title'] ) ? $args['title'] : 'Untitled' ),
            'post_content' => wp_kses_post( isset( $args['content'] ) ? $args['content'] : '' ),
            'post_status'  => sanitize_key( isset( $args['status'] ) ? $args['status'] : 'draft' ),
            'post_type'    => sanitize_key( isset( $args['post_type'] ) ? $args['post_type'] : 'post' ),
        );
        if ( ! empty( $args['excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        }
        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $this->rpc_error( $id, -32603, $post_id->get_error_message() );
        }
        return $this->tool_result( $id, array(
            'id'      => $post_id,
            'url'     => get_permalink( $post_id ),
            'created' => true,
        ) );
    }

    private function tool_update_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post_id = (int) $args['id'];
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . $post_id );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post_id );
        }
        $post_data = array( 'ID' => $post_id );
        if ( isset( $args['title'] ) )   $post_data['post_title']   = sanitize_text_field( $args['title'] );
        if ( isset( $args['content'] ) ) $post_data['post_content'] = wp_kses_post( $args['content'] );
        if ( isset( $args['excerpt'] ) ) $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        if ( isset( $args['status'] ) )  $post_data['post_status']  = sanitize_key( $args['status'] );
        $result = wp_update_post( $post_data, true );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array(
            'id'      => $post_id,
            'updated' => true,
            'url'     => get_permalink( $post_id ),
        ) );
    }

    private function tool_delete_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post_id = (int) $args['id'];
        if ( ! current_user_can( 'delete_post', $post_id ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to delete post ' . $post_id );
        }
        $force = ! empty( $args['force'] );
        $result = wp_delete_post( $post_id, $force );
        if ( ! $result ) {
            return $this->rpc_error( $id, -32603, 'Failed to delete post ' . $post_id );
        }
        return $this->tool_result( $id, array(
            'id'      => $post_id,
            'deleted' => true,
            'trashed' => ! $force,
        ) );
    }

    private function tool_get_toolkit_settings( $id ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_options required' );
        }
        $settings = get_option( 'ds_toolkit_settings', array() );
        // Omit large CSS/JS content by default — request explicitly if needed
        $summary = $settings;
        unset( $summary['global_css_content'], $summary['global_js_content'] );
        return $this->tool_result( $id, array(
            'settings' => $summary,
            'note'     => 'global_css_content and global_js_content omitted for brevity. Include them in update_toolkit_settings to modify.',
        ) );
    }

    private function tool_update_toolkit_settings( $id, $args ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_options required' );
        }
        if ( empty( $args['settings'] ) || ! is_array( $args['settings'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing or invalid argument: settings (must be an object/map)' );
        }
        $allowed = array_flip( array(
            'enable_login_branding',
            'hide_fl_assistant',
            'acf_css_vars_enabled',
            'acf_css_vars_mappings',
            'getsubmenu_enabled',
            'current_year_enabled',
            'forminator_email_partner_enabled',
            'forminator_email_partner_fallback',
            'global_css_enabled',
            'global_css_content',
            'global_js_enabled',
            'global_js_content',
            'child_pages_enabled',
            'child_pages_template_id',
            'child_pages_columns',
            'child_pages_columns_tablet',
            'child_pages_columns_mobile',
        ) );
        $current  = get_option( 'ds_toolkit_settings', array() );
        $updated  = array();
        $rejected = array();
        foreach ( $args['settings'] as $key => $value ) {
            if ( isset( $allowed[ $key ] ) ) {
                $current[ $key ] = $value;
                $updated[]       = $key;
            } else {
                $rejected[] = $key;
            }
        }
        update_option( 'ds_toolkit_settings', $current );
        return $this->tool_result( $id, array(
            'updated_keys'  => $updated,
            'rejected_keys' => $rejected,
        ) );
    }

    // -------------------------------------------------------------------------
    // Tool Schema (returned by tools/list)
    // -------------------------------------------------------------------------

    private function get_tools_schema() {
        return array(
            array(
                'name'        => 'list_posts',
                'description' => 'List WordPress posts or pages. Returns ID, title, status, type, URL, and dates.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_type' => array( 'type' => 'string',  'description' => 'Post type: post, page, or any custom type. Default: post' ),
                        'per_page'  => array( 'type' => 'integer', 'description' => 'Number of results to return (max 100). Default: 20' ),
                        'search'    => array( 'type' => 'string',  'description' => 'Keyword to search titles and content' ),
                        'status'    => array( 'type' => 'string',  'description' => 'Filter by status: publish, draft, private, any. Default: any' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_post',
                'description' => 'Get the full content of a WordPress post or page by its ID.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id' => array( 'type' => 'integer', 'description' => 'WordPress post ID' ),
                    ),
                ),
            ),
            array(
                'name'        => 'create_post',
                'description' => 'Create a new WordPress post or page. Returns the new post ID and URL.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'title' ),
                    'properties' => array(
                        'title'     => array( 'type' => 'string', 'description' => 'Post title' ),
                        'content'   => array( 'type' => 'string', 'description' => 'Post content (HTML allowed)' ),
                        'excerpt'   => array( 'type' => 'string', 'description' => 'Post excerpt / summary' ),
                        'status'    => array( 'type' => 'string', 'description' => 'Post status: draft, publish, private. Default: draft' ),
                        'post_type' => array( 'type' => 'string', 'description' => 'Post type: post or page. Default: post' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_post',
                'description' => 'Update an existing WordPress post or page. Only provided fields are changed.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'      => array( 'type' => 'integer', 'description' => 'WordPress post ID to update' ),
                        'title'   => array( 'type' => 'string',  'description' => 'New title' ),
                        'content' => array( 'type' => 'string',  'description' => 'New content (HTML allowed)' ),
                        'excerpt' => array( 'type' => 'string',  'description' => 'New excerpt' ),
                        'status'  => array( 'type' => 'string',  'description' => 'New status: draft, publish, private' ),
                    ),
                ),
            ),
            array(
                'name'        => 'delete_post',
                'description' => 'Delete (or trash) a WordPress post or page.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'    => array( 'type' => 'integer', 'description' => 'WordPress post ID to delete' ),
                        'force' => array( 'type' => 'boolean', 'description' => 'If true, permanently deletes. If false (default), moves to trash.' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_toolkit_settings',
                'description' => 'Read the current DS Toolkit settings — feature toggles, column counts, template IDs, etc. Requires manage_options capability.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'update_toolkit_settings',
                'description' => 'Update one or more DS Toolkit settings by passing a settings object. Requires manage_options capability.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'settings' ),
                    'properties' => array(
                        'settings' => array(
                            'type'        => 'object',
                            'description' => 'Key-value map of settings to update. Allowed keys: enable_login_branding, hide_fl_assistant, acf_css_vars_enabled, getsubmenu_enabled, current_year_enabled, forminator_email_partner_enabled, forminator_email_partner_fallback, global_css_enabled, global_css_content, global_js_enabled, global_js_content, child_pages_enabled, child_pages_template_id, child_pages_columns, child_pages_columns_tablet, child_pages_columns_mobile',
                        ),
                    ),
                ),
            ),
        );
    }

    // -------------------------------------------------------------------------
    // JSON-RPC Helpers
    // -------------------------------------------------------------------------

    private function rpc_result( $id, $result ) {
        return new WP_REST_Response( array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ), 200 );
    }

    /**
     * Wraps tool output in MCP content envelope.
     */
    private function tool_result( $id, $data ) {
        return $this->rpc_result( $id, array(
            'content' => array(
                array(
                    'type' => 'text',
                    'text' => wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ),
                ),
            ),
        ) );
    }

    private function rpc_error( $id, $code, $message ) {
        return new WP_REST_Response( array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => array(
                'code'    => $code,
                'message' => $message,
            ),
        ), 200 );
    }
}
