# Contributing to WonderWay

We welcome contributions to WonderWay! This document provides guidelines for contributing to the project.

## Code of Conduct

By participating in this project, you agree to abide by our [Code of Conduct](CODE_OF_CONDUCT.md).

## How to Contribute

### Reporting Issues

1. Check existing issues to avoid duplicates
2. Use the issue template when creating new issues
3. Provide clear reproduction steps
4. Include relevant system information

### Submitting Changes

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/amazing-feature`
3. **Make** your changes following our coding standards
4. **Test** your changes thoroughly
5. **Commit** with clear messages: `git commit -m 'Add amazing feature'`
6. **Push** to your branch: `git push origin feature/amazing-feature`
7. **Submit** a Pull Request

### Development Setup

```bash
# Clone your fork
git clone https://github.com/your-username/wonderway-backend.git
cd wonderway-backend

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run tests
php artisan test
```

## Coding Standards

### PHP Standards
- Follow **PSR-12** coding standard
- Use **type hints** for all parameters and return types
- Write **PHPDoc** comments for all methods
- Use **meaningful variable names**

### Laravel Conventions
- Follow **Laravel naming conventions**
- Use **Eloquent relationships** properly
- Implement **Form Requests** for validation
- Use **Resource classes** for API responses

### Testing
- Write **unit tests** for all new features
- Maintain **minimum 80% code coverage**
- Use **Feature tests** for API endpoints
- Mock external services in tests

## Pull Request Guidelines

### Before Submitting
- [ ] All tests pass
- [ ] Code follows style guidelines
- [ ] Documentation is updated
- [ ] No merge conflicts exist

### PR Description
- Describe what changes were made
- Reference related issues
- Include screenshots for UI changes
- List breaking changes if any

## Security

If you discover a security vulnerability, please follow our [Security Policy](SECURITY.md) instead of opening a public issue.

## Questions?

Feel free to open an issue for questions or join our discussions.

Thank you for contributing to WonderWay! 🚀