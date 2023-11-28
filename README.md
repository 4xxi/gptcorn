# GPTCorn

## Introduction

Welcome to GPTCorn, a powerful and user-friendly tool for prompt engineering. Ideal for developers, copywriters,
students, researchers, and more, GPTCorn offers an innovative platform for managing and improving interactions with AI,
specifically through OpenAI models.

### Project's Purpose and Scope

**Purpose**: GPTCorn is developed to revolutionize prompt engineering with AI applications. It simplifies and enriches
the process, making it more intuitive, efficient, and effective for various users.

**Scope**: GPTCorn goes beyond template management. It's a comprehensive platform that includes features like contextual
example integration, collaborative workspaces, sophisticated history tracking of prompt runs, and the ability to run
prompts via OpenAI models, fostering both individual creativity and collaborative innovation.

## Features

- Comprehensive Prompt Template Management: Organize and access your prompts in one central location.
- Contextual Examples for Placeholders: Enhance your prompts with contextual examples, reusable across different
  templates.
- Collaborative Collections: Share and collaborate on prompt collections with colleagues and the community.
- Favorites and Quick Access: Easily access your favorite and most-used templates.
- Comprehensive History and Editing: Track your prompt history and edit templates for continuous improvement.
- Easy Cloning for Efficiency: Quickly duplicate prompts and templates to streamline your workflow.
- Organized Placeholder Categories: Manage your placeholders effectively with categorization.
- Google OAuth2 for Easy Sign-Up: Get started quickly and securely with Google sign-in.
- Integration with OpenAI Models: Run your prompts through various OpenAI models, leveraging the power of advanced AI.

For a detailed overview of GPTCorn's features, including screencasts and screenshots, visit
our [official website](https://gptcorn.ai).

## Installation Instructions

1. Clone the Repository: Get the GPTCorn code on your computer.
   `git clone git@github.com:4xxi/gptcorn.git`

2. Set Up Your Environment:
    - Make a local copy of the `.env` file and set OPENAI_API_KEY.
    - Start the Docker containers using `docker compose up -d`.

3. Prepare the Application:
    - Build the CSS with `php bin/console tailwind:build`.
    - Compile the assets using `php bin/console asset-map:compile`.

### Xdebug

To enable Xdebug for more effective debugging, set the following environment variables:

```shell
XDEBUG_MODE=debug
XDEBUG_SESSION=1
PHP_IDE_CONFIG="serverName=localhost"
```

### Creating the First User

To create the first user, execute the following console command:

```shell
bin/console app:user:create --email=<email> --password=<password> --admin
```

Note: Use `--admin` to grant admin privileges to the user.

### Admin Panel

Access the admin panel at: `https://localhost/admin`

### Google OAuth Setup

To enable Google OAuth, update these environment variables:

```shell
OAUTH_GOOGLE_CLIENT_ID
OAUTH_GOOGLE_CLIENT_SECRET
```

### OpenAI API Key and Model Configuration

1. Set your OpenAI API Key in the environment variable `OPENAI_API_KEY`.
2. To change the OpenAI model, update the `OPENAI_MODEL` environment variable.

## Contributing

We welcome and appreciate your contributions to GPTCorn! For instructions on how to fork the repository, create
branches, submit pull requests, and follow our coding standards, please refer to
our  [Contributing Guidelines](CONTRIBUTING.md).

## Code of Conduct

Participate respectfully. For more detailed expectations, please read our [Code of Conduct](CODE_OF_CONDUCT.md).

## License

GPTCorn is released under the MIT License. This permissive license is simple and easy to understand and places almost no
restrictions on what you can do with the project. It allows for great freedom in using, modifying, and sharing the
software. For more details, see the [LICENSE](LICENSE) file.
