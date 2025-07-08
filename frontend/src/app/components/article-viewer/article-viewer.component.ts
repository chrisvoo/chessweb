import {Component, input, Input, OnChanges, SimpleChanges} from '@angular/core';
import {DomSanitizer, SafeHtml} from '@angular/platform-browser';
import {Image} from 'primeng/image';
import {NgForOf, NgIf} from '@angular/common';
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
    NgIf,
    NgForOf,
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

  constructor(private sanitizer: DomSanitizer) {}

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['content']) {
      this.parseContent();
    }
  }

  private parseContent(): void {
    if (!this.content()) {
      this.contentParts = [];
      this.imageCount = 0;
      return;
    }

    const tempParts: ContentPart[] = [];
    const tempImages: GalleriaImage[] = [];

    // Regex to split the content by <img> tags, keeping the tags
    const splitContent = this.content().split(/(<img.*?>)/g).filter(part => part);

    splitContent.forEach(part => {
      if (part.startsWith('<img')) {
        // This is an image part
        const srcMatch = part.match(/src="(.*?)"/);
        const styleMatch = part.match(/style="(.*?)"/);

        if (srcMatch) {
          const imageSrc = srcMatch[1];

          tempImages.push({
            itemImageSrc: imageSrc,
            thumbnailImageSrc: imageSrc, // Use the same image for the thumbnail
            alt: 'Article Image',
            title: '' // You can add a title if needed
          });

          tempParts.push({
            type: 'image',
            value: '', // Not needed for image part
            imageAttributes: {
              src: imageSrc,
              style: styleMatch ? styleMatch[1] : 'width: 100px;'
            }
          });
        }
      } else {
        // This is a text part
        tempParts.push({
          type: 'text',
          // Sanitize the HTML to prevent XSS attacks
          value: this.sanitizer.bypassSecurityTrustHtml(part)
        });
      }
    });

    this.contentParts = tempParts;
    this.imagesForGalleria = tempImages;
    this.imageCount = tempImages.length;
  }
}
