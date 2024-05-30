# [Replicate](https://replicate.com/) integration in Nextcloud

This app adds:
* A SpeechToText provider using Whisper
* A Text processing provider
* An Image generation provider

:warning: The smart pickers have been removed from this app
as they are now included in the [Assistant app](https://apps.nextcloud.com/apps/assistant).

## Ethical AI Rating
### Speech-to-Text Rating: 🟡

Positive:
* The software for training and inference of this model is open source
* The trained model is freely available, and thus can be run on-premises

Negative:
* The training data is not freely available, limiting the ability of external parties to check and correct for bias or optimise the model’s performance and CO2 usage.

### Text processing Rating

The rating depends on the model you select to use.

### Image generation Rating

The rating depends on the model you select to use.

Learn more about the Nextcloud Ethical AI Rating [in our blog](https://nextcloud.com/blog/nextcloud-ethical-ai-rating/).

## 🔧 Configuration

### Admin settings

There is a "Artificial intelligence" **admin** settings section to set your Replicate API key.
