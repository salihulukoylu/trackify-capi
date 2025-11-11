<?php
/**
 * Data Hasher
 * 
 * SHA256 hashing ve data normalizasyonu
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Data_Hasher {
    
    /**
     * Email hash'le
     * 
     * @param string $email
     * @return string
     */
    public function hash_email( $email ) {
        if ( empty( $email ) ) {
            return '';
        }
        
        // Normalize: lowercase, trim
        $normalized = strtolower( trim( $email ) );
        
        // Validate
        if ( ! is_email( $normalized ) ) {
            return '';
        }
        
        return hash( 'sha256', $normalized );
    }
    
    /**
     * Telefon hash'le
     * 
     * @param string $phone
     * @return string
     */
    public function hash_phone( $phone ) {
        if ( empty( $phone ) ) {
            return '';
        }
        
        // Normalize: sadece rakamlar
        $normalized = preg_replace( '/[^0-9]/', '', $phone );
        
        if ( empty( $normalized ) ) {
            return '';
        }
        
        return hash( 'sha256', $normalized );
    }
    
    /**
     * Genel text hash'le (isim, şehir, vb.)
     * 
     * @param string $text
     * @return string
     */
    public function hash_text( $text ) {
        if ( empty( $text ) ) {
            return '';
        }
        
        // Normalize: lowercase, trim, whitespace temizle
        $normalized = strtolower( trim( $text ) );
        $normalized = preg_replace( '/\s+/', '', $normalized );
        
        return hash( 'sha256', $normalized );
    }
    
    /**
     * Posta kodu hash'le
     * 
     * @param string $postcode
     * @return string
     */
    public function hash_postcode( $postcode ) {
        if ( empty( $postcode ) ) {
            return '';
        }
        
        // Normalize: sadece alphanumeric, lowercase
        $normalized = strtolower( preg_replace( '/[^a-z0-9]/', '', $postcode ) );
        
        return hash( 'sha256', $normalized );
    }
    
    /**
     * Cinsiyet hash'le
     * 
     * @param string $gender 'm', 'f' veya 'male', 'female'
     * @return string
     */
    public function hash_gender( $gender ) {
        if ( empty( $gender ) ) {
            return '';
        }
        
        // Normalize
        $gender = strtolower( trim( $gender ) );
        
        // 'm' veya 'f' ye dönüştür
        if ( in_array( $gender, array( 'male', 'man', 'erkek' ), true ) ) {
            $gender = 'm';
        } elseif ( in_array( $gender, array( 'female', 'woman', 'kadın' ), true ) ) {
            $gender = 'f';
        }
        
        // Sadece m veya f kabul et
        if ( ! in_array( $gender, array( 'm', 'f' ), true ) ) {
            return '';
        }
        
        return hash( 'sha256', $gender );
    }
    
    /**
     * Doğum tarihi hash'le
     * 
     * @param string $date Format: YYYYMMDD
     * @return string
     */
    public function hash_date_of_birth( $date ) {
        if ( empty( $date ) ) {
            return '';
        }
        
        // Normalize: sadece rakamlar
        $normalized = preg_replace( '/[^0-9]/', '', $date );
        
        // YYYYMMDD formatı kontrol (8 karakter)
        if ( strlen( $normalized ) !== 8 ) {
            return '';
        }
        
        return hash( 'sha256', $normalized );
    }
    
    /**
     * Toplu hash (birden fazla data)
     * 
     * @param array $data
     * @return array
     */
    public function hash_user_data( $data ) {
        $hashed = array();
        
        if ( ! empty( $data['email'] ) ) {
            $hashed['em'] = $this->hash_email( $data['email'] );
        }
        
        if ( ! empty( $data['phone'] ) ) {
            $hashed['ph'] = $this->hash_phone( $data['phone'] );
        }
        
        if ( ! empty( $data['first_name'] ) ) {
            $hashed['fn'] = $this->hash_text( $data['first_name'] );
        }
        
        if ( ! empty( $data['last_name'] ) ) {
            $hashed['ln'] = $this->hash_text( $data['last_name'] );
        }
        
        if ( ! empty( $data['city'] ) ) {
            $hashed['ct'] = $this->hash_text( $data['city'] );
        }
        
        if ( ! empty( $data['state'] ) ) {
            $hashed['st'] = $this->hash_text( $data['state'] );
        }
        
        if ( ! empty( $data['postcode'] ) ) {
            $hashed['zp'] = $this->hash_postcode( $data['postcode'] );
        }
        
        if ( ! empty( $data['country'] ) ) {
            $hashed['country'] = $this->hash_text( $data['country'] );
        }
        
        if ( ! empty( $data['gender'] ) ) {
            $hashed['ge'] = $this->hash_gender( $data['gender'] );
        }
        
        if ( ! empty( $data['date_of_birth'] ) ) {
            $hashed['db'] = $this->hash_date_of_birth( $data['date_of_birth'] );
        }
        
        return $hashed;
    }
    
    /**
     * Hash doğrula (test için)
     * 
     * @param string $value
     * @param string $hash
     * @param string $type email, phone, text
     * @return bool
     */
    public function verify_hash( $value, $hash, $type = 'text' ) {
        $computed_hash = '';
        
        switch ( $type ) {
            case 'email':
                $computed_hash = $this->hash_email( $value );
                break;
            case 'phone':
                $computed_hash = $this->hash_phone( $value );
                break;
            default:
                $computed_hash = $this->hash_text( $value );
                break;
        }
        
        return hash_equals( $computed_hash, $hash );
    }
}