import Quill from 'quill';

const Image: any = Quill.import('formats/image');
const ATTRIBUTES = ['alt', 'height', 'width', 'style', 'class'];

export class CustomImageBlot extends Image {
  static blotName = 'customImage';
  static tagName = 'img';


  /**
   * Converts the HTML tag to image blot
   * @param value
   */
  static create(value: any) {

    let node = super.create();

    console.log(value)
    node.setAttribute('style', value.style);
    node.setAttribute('src', value.src);
    node.setAttribute('class', value.class);

    return node;
  }

  /**
   * Converts the image blot to HTML tag
   * @param node
   */
  static value(node: any) {

    const blot = {
      src: node.getAttribute('src'),
      style: node.getAttribute('style'),
      class: node.getAttribute('class'),
    };

    return blot;
  }
}
