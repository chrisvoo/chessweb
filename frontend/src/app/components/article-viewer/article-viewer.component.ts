import {Component, input, Input, OnChanges, SimpleChanges} from '@angular/core';
import {DomSanitizer, SafeHtml} from '@angular/platform-browser';
import {Image} from 'primeng/image';

import {GalleriaModule} from 'primeng/galleria';

type ContentPart = {
  type: 'text' | 'image';
  value: string | SafeHtml;
  imageAttributes?: { src: string; style: string };
};

type GalleriaImage = {
  itemImageSrc: string;
  thumbnailImageSrc: string;
  alt: string;
  title: string;
};

@Component({
  selector: 'chess-article-viewer',
  imports: [
    Image,
    GalleriaModule
],
  templateUrl: './article-viewer.component.html',
  styleUrl: './article-viewer.component.css',
  standalone: true,
})
export class ArticleViewerComponent implements OnChanges {
  content = input.required<string>();
  contentParts: ContentPart[] = [];
  imagesForGalleria: GalleriaImage[] = [];
  imageCount = 0;
  activeIndex: number = 0;

  responsiveOptions: any[] = [
    {
      breakpoint: '1024px',
      numVisible: 5
    },
    {
      breakpoint: '768px',
      numVisible: 3
    },
    {
      breakpoint: '560px',
      numVisible: 1
    }
  ];

  constructor(private sanitizer: DomSanitizer) {}

  private reset(): void {
    this.contentParts = [];
    this.imagesForGalleria = [];
    this.imageCount = 0;
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['content']) {
      this.parseContent();
    }
  }

  private parseContent(): void {
    const rawContent = this.content();

    if (!rawContent) {
      this.reset();
      return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(rawContent, 'text/html');
    const imgElements = Array.from(doc.querySelectorAll('img'));
    this.imageCount = imgElements.length;
    this.activeIndex = 0;

    this.imagesForGalleria = imgElements.map(img => ({
      itemImageSrc: img.getAttribute('src') || '',
      thumbnailImageSrc: img.getAttribute('src') || '',
      alt: img.getAttribute('alt') || 'Article Image',
      title: img.getAttribute('title') || ''
    }));

    this.contentParts = [];

    if (this.imageCount > 1) {
      // MULTI-IMAGE MODE (Galleria)
      // We want to show the Text *without* the images (Galleria is shown separately)

      imgElements.forEach(img => {
        // If the image is wrapped in an anchor <a>, remove the anchor too
        const parent = img.parentElement;
        if (parent && parent.tagName === 'A') {
          // Remove the entire <a> tag from the DOM
          parent.remove();
        } else {
          // Otherwise just remove the image tag
          img.remove();
        }
      });

      // The doc.body now contains only the text (with images removed)
      this.contentParts.push({
        type: 'text',
        value: this.sanitizer.bypassSecurityTrustHtml(doc.body.innerHTML)
      });

    } else if (this.imageCount === 1) {
      // SINGLE IMAGE MODE
      // We want to preserve the "Text -> Image -> Text" flow?
      // Or just render as is?
      // Based on your template logic for case(1), we need to split parts.
      // Reverting to a simple safe split for the single image case is easiest
      // to keep your current template working for single images.

      // Let's use the split logic just for this case, but cleaner:
      this.parseSingleImageContent(rawContent);
    } else {
      // NO IMAGES
      this.contentParts.push({
        type: 'text',
        value: this.sanitizer.bypassSecurityTrustHtml(rawContent)
      });
    }

    console.log(this.imagesForGalleria)
  }

  // Helper for the single image case (preserves flow)
  private parseSingleImageContent(html: string): void {
    // Simple split to separate the image from text
    const parts = html.split(/(<img[^>]*?>)/g);

    parts.forEach(part => {
      if (part.startsWith('<img')) {
        // Extract src for the Single Image View
        const srcMatch = part.match(/src="(.*?)"/);
        this.contentParts.push({
          type: 'image',
          value: '',
          imageAttributes: {
            src: srcMatch ? srcMatch[1] : '',
            style: 'width: 100%'
          }
        });
      } else if (part.trim() !== '') {
        // Clean up any ghost <a> tags that might have wrapped the image
        // This regex removes a trailing <a...> or a leading </a>
        let cleanedText = part.replace(/<a[^>]*?>\s*$/gi, '').replace(/^\s*<\/a>/gi, '');

        if (cleanedText.trim()) {
          this.contentParts.push({
            type: 'text',
            value: this.sanitizer.bypassSecurityTrustHtml(cleanedText)
          });
        }
      }
    });
  }
}
