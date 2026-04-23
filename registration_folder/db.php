<?php
// db.php - Supabase REST API Wrapper (Port 443 Compatible)

class Supabase {
    private $url;
    private $key;

    public function __construct() {
        // Hardcoded for simplicity in deployment
        $this->url = 'https://kugkphvgwdibhddoamkx.supabase.co';
        $this->key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt1Z2twaHZnd2RpYmhkZG9hbWt4Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjkyMjc2MDAsImV4cCI6MjA4NDgwMzYwMH0.Q6S89fx8_FzXu8hNbZWVyqGvoUliOW4r30nAnOXAEfU';
    }

    public function request($method, $endpoint, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

        $fullUrl = $this->url . '/rest/v1/' . $endpoint;
        
        if ($method === 'GET' && $data) {
             $fullUrl .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        if ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);

        return json_decode($response, true);
    }

    public function from($table) {
        return new SupabaseQueryBuilder($this, $table);
    }
    
    // Direct raw request to RPC (function)
    public function rpc($functionName, $params = []) {
        $ch = curl_init();
        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ];
        
        $fullUrl = $this->url . '/rest/v1/rpc/' . $functionName;
        
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

class SupabaseQueryBuilder {
    private $supabase;
    private $table;
    private $params = [];

    public function __construct($supabase, $table) {
        $this->supabase = $supabase;
        $this->table = $table;
        $this->params['select'] = '*';
    }

    public function select($columns = '*') {
        $this->params['select'] = $columns;
        return $this;
    }

    public function eq($column, $value) {
        $this->params[$column] = 'eq.' . $value;
        return $this;
    }
    
    public function order($column, $direction = 'asc') {
        $this->params['order'] = $column . '.' . $direction;
        return $this;
    }
    
    public function limit($limit) {
         $this->params['limit'] = $limit;
         return $this;
    }

    public function get() {
        // Handle select/eq/limit via REST params
        // This is a simplified builder. Code needs to adapt.
        // But for RPC calls (our main goal), we bypass this.
        return $this->supabase->request('GET', $this->table, $this->params);
    }

    public function insert($data) {
        return $this->supabase->request('POST', $this->table, $data);
    }

    public function update($data) {
        // Build query string from params for filtering
        $query = http_build_query($this->params);
        $endpoint = $this->table . ($query ? '?' . $query : '');
        return $this->supabase->request('PATCH', $endpoint, $data);
    }
}

$supabase = new Supabase();
?>
