# WonderWay Backend Infrastructure
terraform {
  required_version = ">= 1.0"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region
}

variable "aws_region" {
  description = "AWS region"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Environment name"
  type        = string
  default     = "production"
}

variable "app_name" {
  description = "Application name"
  type        = string
  default     = "wonderway"
}

# VPC
resource "aws_vpc" "main" {
  cidr_block           = "10.0.0.0/16"
  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = {
    Name        = "${var.app_name}-vpc"
    Environment = var.environment
  }
}

# RDS Database
resource "aws_db_instance" "main" {
  identifier     = "${var.app_name}-db"
  engine         = "mysql"
  engine_version = "8.0"
  instance_class = "db.t3.medium"
  
  allocated_storage = 100
  storage_type      = "gp2"
  storage_encrypted = true

  db_name  = "wonderway"
  username = "admin"
  password = random_password.db_password.result

  skip_final_snapshot = true

  tags = {
    Name        = "${var.app_name}-database"
    Environment = var.environment
  }
}

# S3 Bucket
resource "aws_s3_bucket" "media" {
  bucket = "${var.app_name}-media-${random_id.bucket_suffix.hex}"

  tags = {
    Name        = "${var.app_name}-media"
    Environment = var.environment
  }
}

# Random resources
resource "random_password" "db_password" {
  length  = 16
  special = true
}

resource "random_id" "bucket_suffix" {
  byte_length = 4
}

# Outputs
output "database_endpoint" {
  value = aws_db_instance.main.endpoint
}

output "s3_bucket" {
  value = aws_s3_bucket.media.id
}