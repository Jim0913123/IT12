-- Migration: Add Void Tracking to Sale Items Table
-- Date: 2026-03-02
-- Purpose: Enable soft voiding of sale items with audit trail

ALTER TABLE sale_items ADD COLUMN (
    is_voided TINYINT(1) DEFAULT 0,
    voided_by INT NULL,
    void_reason TEXT NULL,
    voided_at DATETIME NULL,
    FOREIGN KEY (voided_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Add index for efficient querying
CREATE INDEX idx_is_voided ON sale_items(is_voided);
CREATE INDEX idx_voided_by ON sale_items(voided_by);
CREATE INDEX idx_voided_at ON sale_items(voided_at);

-- Add index for sale_id to improve joins
CREATE INDEX idx_sale_id ON sale_items(sale_id);
