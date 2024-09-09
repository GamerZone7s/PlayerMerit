# PlayerMerit Plugin

**Version**: 1.0.0  
**API**: 5.0.0  
**Author**: GamerZone7s

## Overview
The **PlayerMerit** plugin is designed to manage and track player merits based on their actions in the game. Players start with a default merit score, and their merits decrease when they kill other players. If a player’s merit points fall below zero, they will be automatically banned.

## Features
- **Merit System**: Players start with a configurable default merit score.
- **Merit Deduction**: Killing another player deducts 10 merit points.
- **World Specific**: The merit system is only enabled in specific worlds as defined in the config file.
- **Merit Warnings**: Players receive warnings when their merit points reach zero.
- **Automatic Ban**: Players with negative merit points are automatically banned.
- **Merit Check Command**: Check another player's merit points using the `/meritcheck` command.

## Installation
1. Download the latest release of the PlayerMerit plugin.
2. Place the plugin file in your PocketMine-MP `plugins` folder.
3. Start or restart your server.

## Configuration
The `config.yml` file allows you to customize:
- **default_merit**: The starting merit points for new players (default: `100`).
- **enabled_worlds**: List of worlds where the merit system will be active (default: `world`, `world_nether`, `world_end`).

```yaml
# Example config.yml
default_merit: 100
enabled_worlds:
  - world
  - world_nether
  - world_end