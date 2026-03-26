<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * DS Toolkit MCP Server
 *
 * Registers /wp-json/ds-toolkit/v1/mcp — a Model Context Protocol endpoint
 * (JSON-RPC 2.0, protocol 2024-11-05). Each tool group can be independently
 * enabled/disabled from the MCP tab in DS Toolkit settings.
 *
 * Auth: HTTP Basic Auth + WordPress Application Passwords (WP 5.6+)
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
        if ( $request->get_method() === 'GET' ) {
            return true;
        }
        return is_user_logged_in() && current_user_can( 'edit_posts' );
    }

    public function handle_request( WP_REST_Request $request ) {
        if ( $request->get_method() === 'GET' ) {
            return new WP_REST_Response( array(
                'server'   => 'DS Toolkit MCP',
                'version'  => DS_TOOLKIT_VERSION,
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
    // Group Access
    // -------------------------------------------------------------------------

    /**
     * Check if a tool group is enabled. Defaults to true if the key isn't set yet.
     */
    private function is_group_enabled( $key ) {
        static $settings = null;
        if ( $settings === null ) {
            $settings = get_option( 'ds_toolkit_settings', array() );
        }
        return ! isset( $settings[ $key ] ) || ! empty( $settings[ $key ] );
    }

    /**
     * Returns the group key for a given post type slug.
     */
    private function group_for_post_type( $post_type ) {
        return in_array( $post_type, array( 'post', 'page' ), true )
            ? 'mcp_posts_pages_enabled'
            : 'mcp_cpt_enabled';
    }

    // -------------------------------------------------------------------------
    // MCP Methods
    // -------------------------------------------------------------------------

    private function handle_initialize( $id, $params ) {
        return $this->rpc_result( $id, array(
            'protocolVersion' => '2024-11-05',
            'capabilities'    => array( 'tools' => new stdClass() ),
            'serverInfo'      => array(
                'name'    => 'DS Toolkit',
                'version' => DS_TOOLKIT_VERSION,
            ),
            'instructions' => 'DS Toolkit MCP server for WordPress. Tools are grouped by type — Posts & Pages, Custom Post Types, Taxonomies, ACF Fields, and Toolkit Settings. Each group can be enabled/disabled in DS Toolkit > MCP tab.',
        ) );
    }

    private function handle_tools_list( $id ) {
        $all_tools = $this->get_tools_schema();
        $available = array_values( array_filter( $all_tools, array( $this, 'is_tool_available' ) ) );
        return $this->rpc_result( $id, array( 'tools' => $available ) );
    }

    /**
     * Check if a tool should appear in tools/list based on group settings.
     */
    private function is_tool_available( $tool ) {
        $name = $tool['name'];
        // Post & Pages + CPT tools — show if either group is enabled
        $post_tools = array( 'list_posts', 'get_post', 'create_post', 'update_post', 'delete_post' );
        if ( in_array( $name, $post_tools, true ) ) {
            return $this->is_group_enabled( 'mcp_posts_pages_enabled' )
                || $this->is_group_enabled( 'mcp_cpt_enabled' );
        }
        $map = array(
            'list_post_types'         => 'mcp_cpt_enabled',
            'list_taxonomies'         => 'mcp_taxonomies_enabled',
            'list_terms'              => 'mcp_taxonomies_enabled',
            'get_term'                => 'mcp_taxonomies_enabled',
            'create_term'             => 'mcp_taxonomies_enabled',
            'update_term'             => 'mcp_taxonomies_enabled',
            'delete_term'             => 'mcp_taxonomies_enabled',
            'set_post_terms'          => 'mcp_taxonomies_enabled',
            'get_post_fields'         => 'mcp_acf_enabled',
            'update_post_fields'      => 'mcp_acf_enabled',
            'get_toolkit_settings'    => 'mcp_toolkit_settings_enabled',
            'update_toolkit_settings' => 'mcp_toolkit_settings_enabled',
        );
        return isset( $map[ $name ] ) ? $this->is_group_enabled( $map[ $name ] ) : true;
    }

    private function handle_tools_call( $id, $params ) {
        $name      = isset( $params['name'] ) ? $params['name'] : '';
        $arguments = isset( $params['arguments'] ) ? $params['arguments'] : array();

        switch ( $name ) {
            // Posts & Pages / CPT
            case 'list_posts':          return $this->tool_list_posts( $id, $arguments );
            case 'get_post':            return $this->tool_get_post( $id, $arguments );
            case 'create_post':         return $this->tool_create_post( $id, $arguments );
            case 'update_post':         return $this->tool_update_post( $id, $arguments );
            case 'delete_post':         return $this->tool_delete_post( $id, $arguments );
            // CPT discovery
            case 'list_post_types':     return $this->tool_list_post_types( $id );
            // Taxonomies
            case 'list_taxonomies':     return $this->tool_list_taxonomies( $id );
            case 'list_terms':          return $this->tool_list_terms( $id, $arguments );
            case 'get_term':            return $this->tool_get_term( $id, $arguments );
            case 'create_term':         return $this->tool_create_term( $id, $arguments );
            case 'update_term':         return $this->tool_update_term( $id, $arguments );
            case 'delete_term':         return $this->tool_delete_term( $id, $arguments );
            case 'set_post_terms':      return $this->tool_set_post_terms( $id, $arguments );
            // ACF / Custom Fields
            case 'get_post_fields':     return $this->tool_get_post_fields( $id, $arguments );
            case 'update_post_fields':  return $this->tool_update_post_fields( $id, $arguments );
            // Toolkit Settings
            case 'get_toolkit_settings':    return $this->tool_get_toolkit_settings( $id );
            case 'update_toolkit_settings': return $this->tool_update_toolkit_settings( $id, $arguments );
            default:
                return $this->rpc_error( $id, -32602, 'Unknown tool: ' . $name );
        }
    }

    // -------------------------------------------------------------------------
    // Posts & Pages / CPT Tools
    // -------------------------------------------------------------------------

    private function tool_list_posts( $id, $args ) {
        $post_type = ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post';
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post_type ) );
        }
        $query_args = array(
            'post_type'      => $post_type,
            'posts_per_page' => ! empty( $args['per_page'] ) ? min( (int) $args['per_page'], 100 ) : 20,
            'post_status'    => ! empty( $args['status'] ) ? sanitize_key( $args['status'] ) : 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }
        $query = new WP_Query( $query_args );
        $posts = array();
        foreach ( $query->posts as $post ) {
            $posts[] = array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'status'   => $post->post_status,
                'type'     => $post->post_type,
                'url'      => get_permalink( $post->ID ),
                'date'     => $post->post_date,
                'modified' => $post->post_modified,
            );
        }
        return $this->tool_result( $id, array( 'total' => $query->found_posts, 'posts' => $posts ) );
    }

    private function tool_get_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        $taxonomies  = get_object_taxonomies( $post->post_type );
        $terms_data  = array();
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'all' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $terms_data[ $taxonomy ] = array_map( function( $t ) {
                    return array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug );
                }, $terms );
            }
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
            'terms'    => $terms_data,
        ) );
    }

    private function tool_create_post( $id, $args ) {
        $post_type = ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post';
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post_type ) );
        }
        if ( ! current_user_can( 'publish_posts' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — publish_posts required' );
        }
        $post_data = array(
            'post_title'   => sanitize_text_field( isset( $args['title'] ) ? $args['title'] : 'Untitled' ),
            'post_content' => wp_kses_post( isset( $args['content'] ) ? $args['content'] : '' ),
            'post_status'  => sanitize_key( isset( $args['status'] ) ? $args['status'] : 'draft' ),
            'post_type'    => $post_type,
        );
        if ( ! empty( $args['excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        }
        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $this->rpc_error( $id, -32603, $post_id->get_error_message() );
        }
        $terms_assigned = array();
        if ( ! empty( $args['terms'] ) && is_array( $args['terms'] ) ) {
            foreach ( $args['terms'] as $taxonomy => $terms ) {
                $result = wp_set_object_terms( $post_id, $terms, sanitize_key( $taxonomy ) );
                if ( ! is_wp_error( $result ) ) {
                    $terms_assigned[] = $taxonomy;
                }
            }
        }
        return $this->tool_result( $id, array( 'id' => $post_id, 'url' => get_permalink( $post_id ), 'created' => true, 'terms_assigned' => $terms_assigned ) );
    }

    private function tool_update_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post->ID );
        }
        $post_data = array( 'ID' => $post->ID );
        if ( isset( $args['title'] ) )   $post_data['post_title']   = sanitize_text_field( $args['title'] );
        if ( isset( $args['content'] ) ) $post_data['post_content'] = wp_kses_post( $args['content'] );
        if ( isset( $args['excerpt'] ) ) $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        if ( isset( $args['status'] ) )  $post_data['post_status']  = sanitize_key( $args['status'] );
        $result = wp_update_post( $post_data, true );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        $terms_assigned = array();
        if ( ! empty( $args['terms'] ) && is_array( $args['terms'] ) ) {
            foreach ( $args['terms'] as $taxonomy => $terms ) {
                $r = wp_set_object_terms( $post->ID, $terms, sanitize_key( $taxonomy ) );
                if ( ! is_wp_error( $r ) ) {
                    $terms_assigned[] = $taxonomy;
                }
            }
        }
        return $this->tool_result( $id, array( 'id' => $post->ID, 'updated' => true, 'url' => get_permalink( $post->ID ), 'terms_assigned' => $terms_assigned ) );
    }

    private function tool_delete_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        if ( ! current_user_can( 'delete_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to delete post ' . $post->ID );
        }
        $force  = ! empty( $args['force'] );
        $result = wp_delete_post( $post->ID, $force );
        if ( ! $result ) {
            return $this->rpc_error( $id, -32603, 'Failed to delete post ' . $post->ID );
        }
        return $this->tool_result( $id, array( 'id' => $post->ID, 'deleted' => true, 'trashed' => ! $force ) );
    }

    // -------------------------------------------------------------------------
    // CPT Discovery Tools
    // -------------------------------------------------------------------------

    private function tool_list_post_types( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_cpt_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_cpt_enabled' );
        }
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $result = array();
        foreach ( $post_types as $pt ) {
            $result[] = array(
                'slug'         => $pt->name,
                'label'        => $pt->label,
                'singular'     => $pt->labels->singular_name,
                'hierarchical' => $pt->hierarchical,
                'supports'     => get_all_post_type_supports( $pt->name ),
                'taxonomies'   => get_object_taxonomies( $pt->name ),
            );
        }
        return $this->tool_result( $id, $result );
    }

    // -------------------------------------------------------------------------
    // Taxonomy Tools
    // -------------------------------------------------------------------------

    private function tool_list_taxonomies( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $result = array();
        foreach ( $taxonomies as $tax ) {
            $result[] = array(
                'slug'        => $tax->name,
                'label'       => $tax->label,
                'singular'    => $tax->labels->singular_name,
                'hierarchical'=> $tax->hierarchical,
                'post_types'  => $tax->object_type,
            );
        }
        return $this->tool_result( $id, $result );
    }

    private function tool_list_terms( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: taxonomy' );
        }
        $terms = get_terms( array(
            'taxonomy'   => sanitize_key( $args['taxonomy'] ),
            'hide_empty' => isset( $args['hide_empty'] ) ? (bool) $args['hide_empty'] : false,
            'number'     => ! empty( $args['per_page'] ) ? min( (int) $args['per_page'], 200 ) : 100,
            'search'     => ! empty( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '',
        ) );
        if ( is_wp_error( $terms ) ) {
            return $this->rpc_error( $id, -32602, $terms->get_error_message() );
        }
        $result = array();
        foreach ( $terms as $term ) {
            $result[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
                'parent'      => $term->parent,
            );
        }
        return $this->tool_result( $id, array( 'taxonomy' => $args['taxonomy'], 'terms' => $result ) );
    }

    private function tool_get_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: taxonomy' );
        }
        $by   = ! empty( $args['slug'] ) ? 'slug' : 'id';
        $val  = ! empty( $args['slug'] ) ? sanitize_text_field( $args['slug'] ) : (int) $args['id'];
        $term = get_term_by( $by, $val, sanitize_key( $args['taxonomy'] ) );
        if ( ! $term ) {
            return $this->rpc_error( $id, -32602, 'Term not found' );
        }
        return $this->tool_result( $id, array(
            'id'          => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'count'       => $term->count,
            'parent'      => $term->parent,
            'taxonomy'    => $term->taxonomy,
        ) );
    }

    private function tool_create_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) || empty( $args['name'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: taxonomy, name' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $term_args = array();
        if ( ! empty( $args['slug'] ) )        $term_args['slug']        = sanitize_title( $args['slug'] );
        if ( ! empty( $args['description'] ) ) $term_args['description'] = sanitize_textarea_field( $args['description'] );
        if ( ! empty( $args['parent'] ) )      $term_args['parent']      = (int) $args['parent'];
        $result = wp_insert_term( sanitize_text_field( $args['name'] ), sanitize_key( $args['taxonomy'] ), $term_args );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array( 'id' => $result['term_id'], 'created' => true ) );
    }

    private function tool_update_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $term_args = array();
        if ( isset( $args['name'] ) )        $term_args['name']        = sanitize_text_field( $args['name'] );
        if ( isset( $args['slug'] ) )        $term_args['slug']        = sanitize_title( $args['slug'] );
        if ( isset( $args['description'] ) ) $term_args['description'] = sanitize_textarea_field( $args['description'] );
        $result = wp_update_term( (int) $args['id'], sanitize_key( $args['taxonomy'] ), $term_args );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array( 'id' => (int) $args['id'], 'updated' => true ) );
    }

    private function tool_delete_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $result = wp_delete_term( (int) $args['id'], sanitize_key( $args['taxonomy'] ) );
        if ( is_wp_error( $result ) || $result === false ) {
            return $this->rpc_error( $id, -32603, 'Failed to delete term' );
        }
        return $this->tool_result( $id, array( 'id' => (int) $args['id'], 'deleted' => true ) );
    }

    private function tool_set_post_terms( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) || ! isset( $args['terms'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy, terms' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post->ID );
        }
        $taxonomy = sanitize_key( $args['taxonomy'] );
        $terms    = is_array( $args['terms'] ) ? $args['terms'] : array( $args['terms'] );
        $append   = ! empty( $args['append'] );
        $result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array(
            'post_id'  => $post->ID,
            'taxonomy' => $taxonomy,
            'term_ids' => $result,
            'append'   => $append,
            'updated'  => true,
        ) );
    }

    // -------------------------------------------------------------------------
    // ACF / Custom Fields Tools
    // -------------------------------------------------------------------------

    private function tool_get_post_fields( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post_id = (int) $args['id'];
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . $post_id );
        }
        if ( function_exists( 'get_fields' ) ) {
            $fields = get_fields( $post_id );
            $fields = $fields ?: array();
        } else {
            $meta   = get_post_meta( $post_id );
            $fields = array();
            foreach ( $meta as $key => $values ) {
                if ( substr( $key, 0, 1 ) === '_' ) continue; // skip internal WP meta
                $fields[ $key ] = count( $values ) === 1 ? $values[0] : $values;
            }
        }
        return $this->tool_result( $id, array(
            'post_id' => $post_id,
            'source'  => function_exists( 'get_fields' ) ? 'acf' : 'post_meta',
            'fields'  => $fields,
        ) );
    }

    private function tool_update_post_fields( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['fields'] ) || ! is_array( $args['fields'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, fields (object)' );
        }
        $post_id = (int) $args['id'];
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . $post_id );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post_id );
        }
        $updated = array();
        if ( function_exists( 'update_field' ) ) {
            foreach ( $args['fields'] as $key => $value ) {
                update_field( sanitize_key( $key ), $value, $post_id );
                $updated[] = $key;
            }
        } else {
            foreach ( $args['fields'] as $key => $value ) {
                update_post_meta( $post_id, sanitize_key( $key ), $value );
                $updated[] = $key;
            }
        }
        return $this->tool_result( $id, array(
            'post_id'      => $post_id,
            'updated_keys' => $updated,
            'source'       => function_exists( 'update_field' ) ? 'acf' : 'post_meta',
        ) );
    }

    // -------------------------------------------------------------------------
    // Toolkit Settings Tools
    // -------------------------------------------------------------------------

    private function tool_get_toolkit_settings( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_toolkit_settings_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_toolkit_settings_enabled' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_options required' );
        }
        $settings = get_option( 'ds_toolkit_settings', array() );
        $summary  = $settings;
        unset( $summary['global_css_content'], $summary['global_js_content'] );
        return $this->tool_result( $id, array(
            'settings' => $summary,
            'note'     => 'global_css_content and global_js_content omitted for brevity.',
        ) );
    }

    private function tool_update_toolkit_settings( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_toolkit_settings_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_toolkit_settings_enabled' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_options required' );
        }
        if ( empty( $args['settings'] ) || ! is_array( $args['settings'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing or invalid argument: settings (must be an object)' );
        }
        $allowed = array_flip( array(
            'enable_login_branding', 'hide_fl_assistant', 'acf_css_vars_enabled',
            'acf_css_vars_mappings', 'getsubmenu_enabled', 'current_year_enabled',
            'forminator_email_partner_enabled', 'forminator_email_partner_fallback',
            'global_css_enabled', 'global_css_content', 'global_js_enabled', 'global_js_content',
            'child_pages_enabled', 'child_pages_template_id', 'child_pages_columns',
            'child_pages_columns_tablet', 'child_pages_columns_mobile',
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
        return $this->tool_result( $id, array( 'updated_keys' => $updated, 'rejected_keys' => $rejected ) );
    }

    // -------------------------------------------------------------------------
    // Tool Schema
    // -------------------------------------------------------------------------

    private function get_tools_schema() {
        return array(
            // Posts & Pages
            array(
                'name'        => 'list_posts',
                'description' => 'List WordPress posts, pages, or custom post types. Returns ID, title, status, type, URL, and dates.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_type' => array( 'type' => 'string',  'description' => 'Post type slug: post, page, events, athletes, staff, teams, or any CPT. Default: post' ),
                        'per_page'  => array( 'type' => 'integer', 'description' => 'Number of results (max 100). Default: 20' ),
                        'search'    => array( 'type' => 'string',  'description' => 'Keyword search' ),
                        'status'    => array( 'type' => 'string',  'description' => 'Filter by status: publish, draft, private, any. Default: any' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_post',
                'description' => 'Get the full content of a post, page, or CPT entry by ID.',
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
                'description' => 'Create a new post, page, or CPT entry. Optionally assign taxonomy terms in the same call.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'title' ),
                    'properties' => array(
                        'title'     => array( 'type' => 'string', 'description' => 'Title' ),
                        'content'   => array( 'type' => 'string', 'description' => 'Content (HTML allowed)' ),
                        'excerpt'   => array( 'type' => 'string', 'description' => 'Excerpt' ),
                        'status'    => array( 'type' => 'string', 'description' => 'draft, publish, private. Default: draft' ),
                        'post_type' => array( 'type' => 'string', 'description' => 'Post type slug. Default: post' ),
                        'terms'     => array( 'type' => 'object', 'description' => 'Taxonomy terms to assign. Keys are taxonomy slugs, values are arrays of term IDs or slugs. Example: {"category":[1,3],"event_category":["basketball"]}' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_post',
                'description' => 'Update an existing post, page, or CPT entry. Only provided fields are changed. Optionally assign taxonomy terms.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'      => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'title'   => array( 'type' => 'string',  'description' => 'New title' ),
                        'content' => array( 'type' => 'string',  'description' => 'New content (HTML allowed)' ),
                        'excerpt' => array( 'type' => 'string',  'description' => 'New excerpt' ),
                        'status'  => array( 'type' => 'string',  'description' => 'New status: draft, publish, private' ),
                        'terms'   => array( 'type' => 'object',  'description' => 'Taxonomy terms to assign. Keys are taxonomy slugs, values are arrays of term IDs or slugs. Example: {"category":[1,3],"event_category":["basketball"]}' ),
                    ),
                ),
            ),
            array(
                'name'        => 'delete_post',
                'description' => 'Delete (or trash) a post, page, or CPT entry.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'    => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'force' => array( 'type' => 'boolean', 'description' => 'true = permanent delete, false = trash (default)' ),
                    ),
                ),
            ),
            // CPT Discovery
            array(
                'name'        => 'list_post_types',
                'description' => 'List all registered public post types on this WordPress site — useful to discover available CPTs like events, athletes, staff, teams.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            // Taxonomies
            array(
                'name'        => 'list_taxonomies',
                'description' => 'List all registered public taxonomies and which post types they belong to.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'list_terms',
                'description' => 'List terms in a taxonomy (e.g. athlete-category, staff-category, team-category).',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy' ),
                    'properties' => array(
                        'taxonomy'   => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'search'     => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'per_page'   => array( 'type' => 'integer', 'description' => 'Max results (default 100, max 200)' ),
                        'hide_empty' => array( 'type' => 'boolean', 'description' => 'Exclude terms with no posts. Default: false' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_term',
                'description' => 'Get a single taxonomy term by ID or slug.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy' ),
                    'properties' => array(
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'id'       => array( 'type' => 'integer', 'description' => 'Term ID (use id or slug)' ),
                        'slug'     => array( 'type' => 'string',  'description' => 'Term slug (use id or slug)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'create_term',
                'description' => 'Create a new term in a taxonomy.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy', 'name' ),
                    'properties' => array(
                        'taxonomy'    => array( 'type' => 'string', 'description' => 'Taxonomy slug' ),
                        'name'        => array( 'type' => 'string', 'description' => 'Term name' ),
                        'slug'        => array( 'type' => 'string', 'description' => 'Term slug (optional, auto-generated from name)' ),
                        'description' => array( 'type' => 'string', 'description' => 'Term description' ),
                        'parent'      => array( 'type' => 'integer','description' => 'Parent term ID (for hierarchical taxonomies)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_term',
                'description' => 'Update an existing taxonomy term.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy' ),
                    'properties' => array(
                        'id'          => array( 'type' => 'integer', 'description' => 'Term ID' ),
                        'taxonomy'    => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'name'        => array( 'type' => 'string',  'description' => 'New name' ),
                        'slug'        => array( 'type' => 'string',  'description' => 'New slug' ),
                        'description' => array( 'type' => 'string',  'description' => 'New description' ),
                    ),
                ),
            ),
            array(
                'name'        => 'set_post_terms',
                'description' => 'Assign or replace taxonomy terms on a post. Use append=true to add without removing existing terms.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy', 'terms' ),
                    'properties' => array(
                        'id'       => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug (e.g. category, event_category, athlete-category)' ),
                        'terms'    => array( 'type' => 'array',   'description' => 'Array of term IDs (integers) or slugs (strings)' ),
                        'append'   => array( 'type' => 'boolean', 'description' => 'true = add to existing terms, false = replace all (default: false)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'delete_term',
                'description' => 'Delete a taxonomy term.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy' ),
                    'properties' => array(
                        'id'       => array( 'type' => 'integer', 'description' => 'Term ID' ),
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                    ),
                ),
            ),
            // ACF / Custom Fields
            array(
                'name'        => 'get_post_fields',
                'description' => 'Get all ACF (or custom meta) fields for a post, page, or CPT entry. Uses get_fields() if ACF is active, otherwise returns post meta.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id' => array( 'type' => 'integer', 'description' => 'Post ID' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_post_fields',
                'description' => 'Update ACF (or custom meta) fields on a post. Pass a fields object with field_name → value pairs.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'fields' ),
                    'properties' => array(
                        'id'     => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'fields' => array( 'type' => 'object',  'description' => 'Object of field_name → value pairs to update' ),
                    ),
                ),
            ),
            // Toolkit Settings
            array(
                'name'        => 'get_toolkit_settings',
                'description' => 'Read current DS Toolkit settings — feature toggles, column counts, template IDs, etc. Requires manage_options.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'update_toolkit_settings',
                'description' => 'Update DS Toolkit settings by passing a key-value settings object. Requires manage_options.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'settings' ),
                    'properties' => array(
                        'settings' => array(
                            'type'        => 'object',
                            'description' => 'Key-value map of settings to update.',
                        ),
                    ),
                ),
            ),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function group_disabled_error( $id, $group_key ) {
        $labels = array(
            'mcp_posts_pages_enabled'      => 'Posts & Pages',
            'mcp_cpt_enabled'              => 'Custom Post Types',
            'mcp_taxonomies_enabled'       => 'Taxonomies',
            'mcp_acf_enabled'              => 'ACF / Custom Fields',
            'mcp_toolkit_settings_enabled' => 'Toolkit Settings',
        );
        $label = isset( $labels[ $group_key ] ) ? $labels[ $group_key ] : $group_key;
        return $this->rpc_error( $id, -32603, '"' . $label . '" tools are disabled. Enable them in DS Toolkit → Settings → MCP tab.' );
    }

    private function rpc_result( $id, $result ) {
        return new WP_REST_Response( array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ), 200 );
    }

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
            'error'   => array( 'code' => $code, 'message' => $message ),
        ), 200 );
    }
}
